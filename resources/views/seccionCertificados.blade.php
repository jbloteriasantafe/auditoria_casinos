@extends('includes.barraNavegacion')
@section('contenidoVista')


        <div id="page-wrapper"> <!-- CONTENIDO DENTRO DEL ESPACIO DE TRABAJO -->

                <div class="row">
                  <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h2><i class="fa fa-fw fa-file-text-o"></i> Certificados</h2>
                        </div>
                    </div>
                  </div>
                </div>

                <br>

                <div class="row">


                  <div class="col-lg-4 col-md-6 col-xs-6">
                    <div class="panel panel-default">
                      <div class="panel-heading enlace-tarjeta" style="background:#b0b0b0;">
                        <center>
                          <i class="fa fa-file-text-o fa-2x"></i>
                          <h4>Certificados Software</h4>
                        </center>
                      </div>
                    </div>
                  </div>

                  <div class="col-lg-4 col-md-6 col-xs-6">
                    <a href="#">
                    <div class="panel panel-default">
                      <div class="panel-heading enlace-tarjeta" style="background:#fff;">
                        <center>
                          <i class="fa fa-file-text-o fa-2x"></i>
                          <h4>Certificados Hardware</h4>
                        </center>
                      </div>
                    </div>
                  </div>
                  </a>


                </div>

                <div class="row">
                  <div class="col-lg-4 col-md-12 col-xs-12"> <!-- columan AGREGAR CASINO -->

                      <div class="panel panel-default">
                        <div class="panel-heading enlace-tarjeta">
                                <div class="boton-icono"><i class="fa fa-plus fa-1x icono"></i></div>
                                <button id="btn-nuevo" class="btn btn-success boton-tarjeta" name="btn-nuevo"><h4>NUEVO GLI SOFT</h4></button>
                        </div>
                      </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-12">
                    <div class="panel panel-default">
                      <div class="panel-heading">
                        <h4>Filtros de Búsqueda</h4>
                      </div>
                      <div class="panel-body">
                        <div class="row">
                          <div class="col-md-2">
                            <h4>Número de Certificado</h4>
                          </div>
                          <div class="col-md-2">
                            <h4>Nombre del Archivo</h4>
                          </div>
                          <div class="col-md-3">
                           <h4>Número de Expediente</h4>
                          </div>
                          <div class="col-md-3">
                           <h4>Casino</h4>
                          </div>
                         </div>
                         <div class="row">
                           <div class="col-md-2">
                             <input id="nuevo" type="text" class="form-control">
                           </div>
                           <div class="col-md-2">
                             <input id="nuevo2" type="text" class="form-control">
                           </div>
                           <div class="col-md-3">
                             <div class="col-xs-4">

                               <input id="nro_exp_org" type="text" class="form-control">
                             </div>
                             <div class="col-xs-5">
                               <div class="row">
                                 <input id="nro_exp_interno" type="text" class="form-control">
                               </div>
                             </div>
                             <div class="col-xs-3">
                               <input id="nro_exp_control" type="text" class="form-control">

                             </div>
                           </div>
                           <div class="col-md-2">
                             <div class="form-group">
                              <select class="form-control" id="sel1">
                                <option value="0">-Casino-</option>
                                <option value="1">Santa Fe</option>
                              </select>
                            </div>
                           </div>
                           <button id="buscarResolucion" class="btn btn-primary btn-lg" type="button" name="button"><i class="fa fa-search fa-1x"></i> Buscar</button>
                           </div>
                         </div>
                      </div>
                    </div>
                </div>

                <div class="row">
                  <div class="row">
                    <div class="col-md-12"> <!-- columna TABLA CASINOS -->
                      <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4>Certificados GLI Software Registrados en el Sistema:</h4>
                        </div>
                        <div class="panel-body">
                          <table class="table">
                            <thead>
                              <tr>
                                <th>NÚMERO DE CERTIFICADO</th>
                                <th>NOMBRE DEL ARCHIVO</th>
                                <th>RESULTADO EVALUACIÓN</th>
                                <th>ACCIÓN</th>
                                <!-- <th>ACCIÓN</th> -->
                              </tr>
                            </thead>
                            <tbody>
                              <tr id=>
                                <td>635123</td>
                                <td>Santa Fe</td>
                                <td>XXXXXX X X X</td>
                                <td>
                                  <button type="button" class="btn btn-warning" ><i class="fa fa-edit"></i> Modificar</button>
                                  <button type="button" class="btn btn-danger" ><i class="fa fa-trash"></i> Eliminar</button>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div> <!--/columna TABLA -->

                  </div>
                </div>

            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- /#page-wrapper // FINAL CONTENIDO DENTRO DEL ESPACIO DE TRABAJO-->

    </div>
    <!-- /#wrapper -->

    <!-- Modal modificar-->
    <div id="modalModificar" class="modal fade" role="dialog">
      <div class="modal-dialog">

        <!-- Modal content-->
        <div class="modal-content">
          <div class="modal-header" style="background: #ff9d2d;">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title" style="color: #fff;">Modificar campos</h4>
          </div>
          <div class="modal-body">
            <br>
            <p>Número de Expediente</p>
            XXX-XXXXX-X<br><br>
            <p>Casino</p>
            Santa Fé<br><br>
            <p>Número de Resolución</p>
            <input type="text" name="" value=""><br><br><br>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-warning" data-dismiss="modal">Modificar</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
          </div>
        </div>
      </div>
    </div><!-- FIN CUERPO MODAL -->

    <!-- Modal -->
    <div id="modalEliminar" class="modal fade" role="dialog">
      <div class="modal-dialog">

        <!-- Modal content-->
        <div class="modal-content">
          <div class="modal-header" style="background: #c9302c;">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title" style="color: #fff;">Eliminar</h4>
          </div>
          <div class="modal-body">
            <br>
            <p>Mensaje de Eliminar Disposición / Resolución </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-dismiss="modal">Eliminar</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
          </div>
        </div>
      </div>
    </div><!-- FIN CUERPO MODAL -->





@endsection
@section('scripts')
    <!-- token -->
    <meta name="_token" content="{!! csrf_token() !!}" />

    <script src="js/seccionCertificados.js"></script>
@endsection
