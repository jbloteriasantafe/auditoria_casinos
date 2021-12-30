@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('estilos')
  <link rel="stylesheet" href="css/paginacion.css">
  <link rel="stylesheet" href="/css/lista-datos.css">
@endsection

@section('contenidoVista')

                <div class="row">
                  <div class="col-lg-12 col-xl-9">

                    <div class="row"> <!-- fila de FILTROS -->
                        <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                              <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
                            </div>
                            <div id="collapseFiltros" class="panel-collapse collapse">
                              <div class="panel-body">
                                <div class="row">
                                  <div class="col-md-3">
                                    <h5>Descripción de formula</h5>
                                    <input id="buscadorDescripcion" class="form-control" placeholder="Descripción de fórmula">
                                  </div>
                                  <div class="col-md-3">
                                    <h5 style="color:#f5f5f5;">boton buscar</h5>
                                    <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                                  </div>
                                </div><br>
                              </div> <!-- /.panel-body -->
                            </div>
                          </div> <!-- /.panel -->
                        </div> <!-- /.col-md-12 -->
                    </div> <!-- Fin de la fila de FILTROS -->


                      <div class="row">
                        <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>CANTIDAD TOTAL DE FÓRMULAS</h4>
                            </div>
                            <div class="panel-body modal-cuerpo">
                              <table id="tablaResultados" class="table table-fixed tablesorter">
                                <thead>
                                  <tr>
                                    <th class="col-xs-2" value="formula.id_formula" estado="">FÓRMULA  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-8" value="formula.cont1" estado="">DESCRIPCIÓN  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2">ACCIONES</th>
                                  </tr>
                                </thead>
                                <tbody id="cuerpoTabla" style="height: 370px;">


                                </tbody>
                              </table>
                              <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                              </div>
                            </div>
                          </div>
                        </div> <!-- Fin del col de los filtros -->

                      </div> <!-- Fin del row de la tabla -->


                    <div class="col-lg-12 col-xl-3">
                      <div class="row">
                        <div class="col-md-12">
                          <a href="" id="btn-nuevo" style="text-decoration: none;">
                              <div class="panel panel-default panelBotonNuevo">
                                <center><img class="imgNuevo" src="/img/logos/formulas_white.png"><center>
                                  <div class="backgroundNuevo"></div>
                                    <div class="row">
                                      <div class="col-xs-12">
                                        <center>
                                          <h5 class="txtLogo">+</h5>
                                          <h4 class="txtNuevo">NUEVA FÓRMULA</h4>
                                        </center>
                                      </div>
                                </div>
                            </div>
                          </a>
                         </div>
                        </div>
                    </div>
            </div> <!--/columna row -->


    <!-- Modal FORMULA -->
    <div class="modal fade" id="modalFormula" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title" id="myModalLabel">| NUEVO FÓRMULA</h3>
                </div>

                <div  id="colapsado" class="collapse in">
                 <div class="modal-body modal-Cuerpo">
                  <form id="frmFormula" name="frmFormula" class="form-horizontal" novalidate="">
                      <div class="form-group error">
                          <div class="col-lg-12">
                            <div id="columna" class="row">
                              <div id="terminoFormula" class="row" style="margin-bottom: 15px;">
                                <div class="col-lg-4 col-lg-offset-1">
                                  <h5>Contador</h5>
                                  <input  id="contador" name="contador" data-cant="0" type="text" class="form-control"  placeholder="Contador" value="">
                                </div>
                                <div class="col-lg-4">
                                  <h5>Operador</h5>
                                  <input id="operador" name="operador" type="text" class="form-control"  placeholder="Operador" value="">
                                </div>
                                <div class="col-lg-3">
                                  <h4>-</h4>
                                  <button id="btn-agregarTermino" class="btn btn-success btn-xs" type="button"><i class="fa fa-fw fa-plus"></i> Mas</button>
                                </div>
                              </div>

                          </div>
                          <span id="alerta_formula" class="alertaSpan"></span>


                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">ACEPTAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                  <input type="hidden" id="id_formula" value="0">
                </div>
              </div>
            </div>
          </div>
    </div>

    <!-- Modal Maquinas -->
    <div class="modal fade" id="modalMaquinas" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizarMaquinas" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoMaq" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title" id="myModalLabel">| Asociar Máquinas</h3>
                </div>

                <div  id="colapsadoMaq" class="collapse in">
                 <div class="modal-body modal-Cuerpo">
                  <form id="frmMaq" name="frmFormula" class="form-horizontal" novalidate="">
                      <div class="form-group error ">
                          <div class="col-lg-12">
                            <div class="row">
                              <div class="col-lg-6">
                                <select class="form-control" id="selectCasino">
                                  <option value="0">-Seleccione Casino-</option>
                                  @foreach ($casinos as $casino)
                                  <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                  @endforeach
                                </select>
                              </div>
                            </div>
                            <br>
                            <div id="buscadores" class="row" hidden="true">
                              <div class="col-xs-6 col-md-6 col-lg-6">

                                <h5>Buscador Islas <i class="fa fa-fw fa-search"></i></h5>
                                <div class="row">

                                  <div class="input-group">
                                      <input id="input-datos-grupo" class="form-control buscadorIsla" type="text" value="" autocomplete="off">
                                      <span class="input-group-btn">
                                        <button class="btn btn-default btn-lista-datos agregarIsla" type="button"><i class="fa fa-plus"></i></button>
                                      </span>
                                  </div>

                                </div>
                                <br>
                                <h5>Buscador Maquinas <i class="fa fa-fw fa-search"></i></h5>
                                <div class="row">
                                  <div class="input-group">
                                      <input id="input-datos-grupo" class="form-control buscadorMaquina" type="text" value="" autocomplete="off">
                                      <span class="input-group-btn">
                                        <button class="btn btn-default btn-lista-datos agregarMaquina" type="button"><i class="fa fa-plus"></i></button>
                                      </span>
                                  </div>
                                </div>

                              </div>

                              <div id="" class="col-md-6 col-lg-6">
                                <div class="row">
                                  <div class="col-md-8 col-lg-8">
                                    <h5>Maquinas Seleccionadas:</h5>
                                  </div>
                                  <div class="col-md-4 col-lg-4 errorVacio">

                                  </div>
                                </div>
                                <ul class="listaMaquinas">
                                </ul>
                              </div>
                            </div>


                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-asociar" >ASOCIAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                  <input type="hidden" id="id_formula" value="0">
                </div>
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
                  <h3 class="modal-titleEliminar" id="myModalLabel">ADVERTENCIA</h3>
                </div>

                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                      <div class="form-group error ">
                          <div class="col-lg-12">
                            <strong id="mensajeEliminar"></strong>
                          </div>
                      </div>

                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-dangerEliminar" id="btn-eliminarFormula" value="0">ELIMINAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                </div>
            </div>
          </div>
    </div>

    <!-- token -->
    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| FÓRMULAS</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Fórmulas</h5>
      <p>
        Creación de nuevas fórmulas con el contador y su respectivo operador. Luego, se podrá asociar
        a máquinas respecto al casino indicado.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="js/paginacion.js" charset="utf-8"></script>
    <!-- JavaScript personalizado -->
    <script src="js/seccionFormulas.js" charset="utf-8"></script>
    <script src="/js/lista-datos.js" type="text/javascript"></script>
    @endsection
