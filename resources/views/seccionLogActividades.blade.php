@extends('includes.dashboard')
@section('estilos')
  <link rel="stylesheet" href="css/bootstrap-datetimepicker.min.css">
  <link rel="stylesheet" href="css/paginacion.css">
@endsection
@section('headerLogo')
<span class="etiquetaLogoUsuarios">@svg('usuario','iconoUsuarios')</span>
@endsection
@section('contenidoVista')
        <div id="page-wrapper">
          <div class="container-fluid">
                <div class="row"> <!-- Tarjeta de FILTROS -->
                  <div class="col-md-12">

                      <div class="panel panel-default">
                        <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                          <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
                        </div>
                        <div id="collapseFiltros" class="panel-collapse collapse">
                          <div class="panel-body">

                            <div class="row">
                              <div class="col-lg-3 col-xs-6">
                                <h5>Usuario</h5>
                                <input id="B_usuario" type="text" class="form-control" placeholder="Usuario" list="usuarios">
                              </div>
                              <div class="col-lg-3 col-xs-6">
                                <h5>Tabla</h5>
                                <select id="B_tabla" class="form-control">
                                  <option value="">- Todas -</option>
                                  @foreach($tablas as $t)
                                  <option>{{$t}}</option>
                                  @endforeach
                                </select>
                              </div>
                              <div class="col-lg-3 col-xs-6">
                                <h5>Acción</h5>
                                <select id="B_accion" class="form-control">
                                  <option value="">- Todas -</option>
                                  @foreach($acciones as $a)
                                  <option>{{$a}}</option>
                                  @endforeach
                                </select>
                              </div>
                              <div class="col-lg-3 col-xs-6">
                                <h5>Fecha</h5>
                                <div class='input-group date' id='dtpFecha'>
                                  <input type='text' class="form-control" id="B_fecha" placeholder="Fecha"/>
                                  <span class="input-group-addon">
                                    <span class="glyphicon glyphicon-calendar"></span>
                                  </span>
                                </div>
                              </div>
                            </div>

                            <br>

                            <div class="row">
                              <div class="col-md-12">
                                <center><button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button></center>
                              </div>
                            </div>

                          </div>
                        </div>

                      </div>

                  </div>
                </div> <!-- / Tarjeta FILTROS -->

                <div class="row"> <!-- Tarjeta TABLA log Actividades -->
                  <div class="col-md-12">
                    <div class="panel panel-default">
                      <div class="panel-heading">
                        <h4>REGISTROS DE ACTIVIDADES DEL SISTEMA</h4>
                      </div>
                      <div class="panel-body">
                        <table id="tablaResultados" class="table table-fixed tablesorter">
                          <thead>
                            <tr>
                              <th class="col-md-2 col-xs-3" value="usuario.nombre" estado="">USUARIO <i class="fas fa-sort"></i></th>
                              <th class="col-md-2 col-xs-4 activa" value="fecha" estado="desc">FECHA <i class="fas fa-sort-down"></i></th>
                              <th class="col-md-2 cabeceraOculta" value="accion" estado="">ACTIVIDAD <i class="fas fa-sort"></i></th>
                              <th class="col-md-2 col-xs-3" value="tabla" estado="">TABLA <i class="fas fa-sort"></i></th>
                              <th class="col-md-2 cabeceraOculta" value="id_entidad" estado="">ID ENTIDAD <i class="fas fa-sort"></i></th>
                              <th class="col-md-2 col-xs-2">ACCIÓN</th>
                            </tr>
                          </thead>
                          <tbody id="cuerpoTabla" style="height: 285px;">

                          </tbody>
                        </table>
                        <!--Comienzo indices paginacion-->
                        <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div> <!-- / Tarjeta TABLA -->

            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->

    <!-- Modal Log Actividad -->
    <div class="modal fade" id="modalLogActividad" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
               <div class="modal-header">
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title modalVerMas" id="myModalLabel">VER DETALLE LOG ACTIVIDAD</h3>
                </div>

                <div class="modal-body" style="font-family: Roboto; color: #aaa;">
                 <div  id="colapsado" class="collapse in">
                  <form id="frmLog" name="frmLog" class="form-horizontal" novalidate="">

                      <div class="row">
                          <div class="col-xs-12">
                            <div class="col-md-6">
                              <h5>FECHA</h5>
                              <input type="text" class="form-control" readonly="true" id="fecha" name="fecha" placeholder="Fecha" value="">
                            </div>
                            <div class="col-md-6">
                              <h5>USUARIO</h5>
                              <input type="text" class="form-control" readonly="true" id="usuario" name="usuario" placeholder="Nombre del usuario" value="">
                            </div>
                          </div>
                      </div>

                      <div class="row">
                          <div class="col-xs-12">
                            <div class="col-md-4">
                              <h5>ACTIVIDAD</h5>
                              <input type="text" class="form-control" readonly="true" id="accion" name="accion" placeholder="Acción" value="">
                            </div>
                            <div class="col-md-4">
                              <h5>TABLA</h5>
                              <input type="text" class="form-control" readonly="true" id="tabla" name="tabla" placeholder="Tabla" value="">
                            </div>
                            <div class="col-md-4">
                              <h5>IDENTIDAD</h5>
                              <input type="text" class="form-control" readonly="true" id="id_entidad" name="id_entidad" placeholder="ID Entidad" value="">
                            </div>
                          </div>
                      </div>

                      <br>
                      <div class="row"> <!-- Tarjeta TABLA log Actividades -->
                        <div class="col-md-12">
                              <table id="tablaDetalleLog" class="table table-hover">
                                <thead>
                                  <tr>
                                    <th class="col-xs-6">CAMPO</th>
                                    <th class="col-xs-6">VALOR</th>
                                  </tr>
                                </thead>
                                <tbody>
                                </tbody>
                              </table>
                        </div>
                      </div> <!-- / Tarjeta TABLA -->

                  </form>
                <div class="modal-footer">
                  <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
                </div>
              </div>
            </div>
          </div>
    </div>
  </div>
<div hidden><datalist id="usuarios">
  @foreach($usuarios as $u)
  <option>{{$u}}</option>
  @endforeach
</datalist></div>

    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| AYUDA LOG DE ACTIVIDADES</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de logs</h5>
      <p>
        Informe detallado que muestra las últimas tareas o acciones realizadas por los usuarios dentro del sistema.
        Estan clasificadas de acuerdo a la actividad, fecha y tabla en la que fue producida.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="js/paginacion.js" charset="utf-8"></script>
    <!-- JavaScript personalizado -->
    <script src="js/seccionLogActividades.js" charset="utf-8"></script>
    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    @endsection
