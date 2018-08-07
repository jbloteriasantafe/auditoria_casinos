@extends('includes.barraNavegacion')
@section('contenidoVista')


        <div class="container-fluid">
          <div class="row">
            <div class="col-md-12 bannerGeneral">
              <h2><i class="flaticon-folder"></i> ADMINISTRAR Formulas</h2>
            </div>
          </div>
        </div>

        <div id="page-wrapper">

            <div class="container-fluid">

                <br>

                <div class="row">
                  <div class="col-md-3">
                    <div class="panel panel-default">
                        <div class="iconoBoton" style="width:20%;">
                            <i class="fa fa-plus"></i>
                        </div>
                        <div  class="textoBoton" style="width:50%;">
                            <h4>NUEVA FORMULA</h4>
                            <button id="btn-nuevo" type="button" name="button">Boton</button>
                        </div>
                    </div>
                  </div>
                  <div class="col-md-6"> <!-- columna TABLA CASINOS -->
                    <div class="panel panel-default">
                      <div class="panel-heading">
                          <h4>Formulas cargadas en el sistema</h4>
                      </div>
                      <div class="panel-body">
                        <table id="tablaFormulas" class="table">
                          <thead>
                            <tr>
                              <th>Formula</th>
                              <th class="acciones">ACCIÓN</th>
                            </tr>
                          </thead>
                          <tbody id="cuerpoTablaFormulas">

                             @foreach ($formulas as $formula)
                             
                              <tr id="{{$formula->id_formula}}">
                                <td class="col-xs-6">Formula {{$formula->id_formula}}</td>
                                <td class="col-xs-6 acciones">
                                  <button class="btn btn-warning btn-detalle modificar" value="{{$formula->id_formula}}"><i class="fa fa-fw fa-pencil"></i> Modificar</button>
                                  <button class="btn btn-danger btn-borrar eliminar" value="{{$formula->id_formula}}"><i class="fa fa-fw fa-trash"></i> Eliminar</button>
                                </td>
                              </tr>
                            @endforeach

                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div> <!--/columna TABLA -->

                </div>
            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- /#page-wrapper -->

    <!-- Modal Casino -->
    <div class="modal fade" id="modalFormula" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-title" id="myModalLabel">Nueva Formula</h3>
                </div>

                <div class="modal-body">
                  <form id="frmFormula" name="frmFormula" class="form-horizontal" novalidate="">
                      <div class="form-group error ">
                          <div class="col-xs-12">
                            <div id="columna" class="row">
                              <div id="terminoFormula" class="row">
                                <div class="col-xs-5">
                                  <h4>Contador</h4>
                                  <input  id="contador" name="contador" data-cant="0" type="text" class="form-control"  placeholder="Contador" value="">
                                </div>
                                <div class="col-xs-5">
                                  <h4>Operador</h4>
                                  <input id="operador" name="operador" type="text" class="form-control"  placeholder="Operador" value="">
                                </div>
                                <div class="col-xs-2">
                                  <br><br><br>
                                  <button id="btn-agregarTermino" class="btn btn-success btn-xs" type="button"><i class="fa fa-fw fa-plus"></i> Mas</button>
                                </div>
                              </div>


                            </div>

                          <br>
                          <p id="textoFormula"></p>
                          <div class="alert alert-danger" role="alert" id=""><span></span></div>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-primary" id="btn-guardar" value="nuevo">GUARDAR</button>
                  <input type="hidden" id="id_formula" value="0">
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
                      <div class="form-group error ">
                          <div class="col-xs-12">
                            <strong id="mensajeEliminar"></strong>
                          </div>
                      </div>

                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-danger" id="btn-eliminarFormula" value="0">ELIMINAR</button>
                </div>
            </div>
          </div>
    </div>


    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection
    @section('scripts')
    <!-- JavaScript personalizado -->
    <script src="js/seccionFormulas.js" charset="utf-8"></script>
    @endsection
