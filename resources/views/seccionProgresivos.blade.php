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
                    <div id="contenedorFiltros" class="row"> <!-- Tarjeta de FILTROS -->
                      <div class="col-md-12">

                      <div class="panel panel-default">
                        <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                          <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
                        </div>
                        <div id="collapseFiltros" class="panel-collapse collapse">
                          <div class="panel-body">
                            <div class="row"> <!-- Primera fila -->
                              <div class="col-lg-4">
                                <h5>Nombre Progresivo</h5>
                                <input id="B_nombre_progresivo" type="text" class="form-control" placeholder="Nombre progresivo">
                              </div>
                              <div class="col-lg-4">
                                <h5>Tipo Progresivo</h5>
                                <select class="form-control" id="B_tipo_progresivo">
                                  <option value="0">Todos los Tipos</option>
                                  @foreach ($tipo_progresivos as $tipo_progresivo)
                                  <option value="{{$tipo_progresivo}}">{{$tipo_progresivo}}</option>
                                  @endforeach
                                </select>
                              </div>
                              <div class="col-lg-4">
                                <h5 style="color:#f5f5f5">Búsqueda</h5>
                                <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                              </div>
                            </div> <!-- / Primera fila -->
                            <br>
                          </div>
                        </div>
                      </div>
                  </div>
                </div> <!-- / Tarjeta FILTROS -->

                <div class="row"> <!-- Tarjeta TABLA Progresivos -->
                  <div class="col-md-12">
                    <div class="panel panel-default">
                      <div class="panel-heading">
                        <h4>ÚLTIMOS PROGRESIVOS</h4>
                      </div>
                      <div class="panel-body">
                        <table id="tablaResultados" class="table table-fixed tablesorter">
                          <thead>
                            <tr>
                              <th class="col-xs-4" value="progresivo.nombre_progresivo" estado="">NOMBRE PROGRESIVO  <i class="fa fa-sort"></i></th>
                              <th class="col-xs-4 activa" value="progresivo.individual" estado="desc">TIPO PROGRESIVO  <i class="fa fa-sort-desc"></i></th>
                              <th class="col-xs-4">ACCIONES</th>
                            </tr>
                          </thead>
                          <tbody id="cuerpoTabla" style="height: 350px;">

                          </tbody>
                        </table>
                        <!--Comienzo indices paginacion-->
                        <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div> <!-- / Tarjeta TABLA -->

            {{-- <div class="col-lg-12 col-xl-3">
             <div class="row">
              <div class="col-lg-12">
               <a href="" id="btn-nuevo" style="text-decoration: none;">
                <div class="panel panel-default panelBotonNuevo">
                    <center><img class="imgNuevo" src="/img/logos/progresivos_white.png"><center>
                    <div class="backgroundNuevo"></div>
                    <div class="row">
                        <div class="col-xs-12">
                          <center>
                              <h5 class="txtLogo">+</h5>
                              <h4 class="txtNuevo">NUEVO PROGRESIVO</h4>
                          </center>
                        </div>
                    </div>
                </div>
               </a>
              </div>
            </div>
          </div> --}}

          <div class="ol-lg-12 col-xl-3">
            <div class="row">
             <div class="col-lg-12">
              <a href="" id="btn-nuevo-ind" style="text-decoration: none;">
               <div class="panel panel-default panelBotonNuevo">
                   <center><img class="imgNuevo" src="/img/logos/progresivos_white.png"><center>
                   <div class="backgroundNuevo"></div>
                   <div class="row">
                       <div class="col-xs-12">
                         <center>
                             <h5 class="txtLogo">+</h5>
                             <h4 class="txtNuevo">NUEVO PROGRESIVO INDIVIDUAL</h4>
                         </center>
                       </div>
                   </div>
               </div>
              </a>
             </div>
           </div>
         </div>

         <div class="ol-lg-12 col-xl-3">
          <div class="row">
           <div class="col-lg-12">
            <a href="" id="btn-nuevo-link" style="text-decoration: none;">
             <div class="panel panel-default panelBotonNuevo">
                 <center><img class="imgNuevo" src="/img/logos/progresivos_white.png"><center>
                 <div class="backgroundNuevo"></div>
                 <div class="row">
                     <div class="col-xs-12">
                       <center>
                           <h5 class="txtLogo">+</h5>
                           <h4 class="txtNuevo">NUEVO PROGRESIVO LINKEADO</h4>
                       </center>
                     </div>
                 </div>
             </div>
            </a>
           </div>
         </div>
       </div>



        </div>
            <!-- /.row -->



    <!-- Modal Progresivo -->
    <div class="modal fade" id="modalProgresivo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-keyboard="false" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header" style="background: #5cb85c;">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title" style="color: #fff;">| NUEVO PROGRESIVO</h3>
                </div>

                <div  id="colapsado" class="collapse in">
                  <div class="modal-body modal-Cuerpo">
                      <div class="row">
                        <div class="col-md-6 col-lg-6">
                          <h5>Nombre Progresivo</h5>
                          <input id="nombre_progresivo" type="text" class="form-control" placeholder="Nombre Progresivo" autocomplete="off">
                          <br>
                          <!-- <div id="alerta-nombre_progresivo" class="alert alert-danger"><span></span></div> -->
                          <span id="alerta-nombre-progresivo" class="alertaSpan"></span>
                        </div>
                        <div class="col-md-6 col-lg-6">
                          <h5>Tipo Progresivo</h5>
                          <select class="form-control" id="selectTipoProgresivos">
                            <option value="0">-Seleccione un tipo-</option>
                            @foreach ($tipo_progresivos as $tipo_progresivo)
                            <option value="{{$tipo_progresivo}}">{{$tipo_progresivo}}</option>
                            @endforeach
                          </select>
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-xs-6 col-md-6 col-lg-6">
                          <h5>Porcentaje Recuperación</h5>
                          <input id="porcentaje_recuperacion" type="text" class="form-control" placeholder="Porcentaje recuperación">
                        </div>
                        <div class="col-xs-6 col-md-6 col-lg-6">
                          <h5>Valor Máximo</h5>
                          <input id="maximo" type="text" class="form-control" placeholder="Valor Máximo">
                        </div>

                      </div>
                      <br>
                      <div hidden="true" id="cuerpo_individual">
                        <div class="row">
                          <div class="col-xs-6 col-md-6 col-lg-6">
                            <div id="modelo_radio" class="row radioGroup">
                              @foreach($casinos as $casino)
                              <div class="col-xs-4">
                                <input id="indiv_{{$casino->id_casino}}" type="radio" value="{{$casino->id_casino}}" name="casinos_individual">
                                <label for="indiv_{{$casino->id_casino}}">{{$casino->nombre}}</label>
                              </div>
                              @endforeach
                            </div>

                            <h5>Buscador Islas <i class="fa fa-fw fa-search"></i></h5>
                            <div class="row">
                              <div class="input-group lista-datos-group">
                                              <input class="form-control buscadorIsla" type="text" value="" autocomplete="off" >
                                              <span class="input-group-btn">
                                                <button class="btn btn-default btn-lista-datos agregarIsla" type="button"><i class="fa fa-plus"></i></button>
                                              </span>
                              </div>

                            </div>
                            <br>
                            <h5>Buscador Maquinas <i class="fa fa-fw fa-search"></i></h5>
                            <div class="row">
                              <div class="input-group lista-datos-group">
                                              <input class="form-control buscadorMaquina" type="text" value="" autocomplete="off" >
                                              <span class="input-group-btn">
                                                <button class="btn btn-default btn-lista-datos agregarMaquina" type="button"><i class="fa fa-plus"></i></button>
                                              </span>
                              </div>
                            </div>

                          </div>

                          <div id="" class="col-md-6 col-lg-6">
                            <h5>Maquinas Seleccionadas:</h5>
                            <ul class="listaMaquinas">
                            </ul>
                          </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-lg-12">
                                <h5>Niveles Progresivo <button class="btn btn-success btn-agregarNivelProgresivo" type="button"><i class="fa fa-fw fa-plus"></i> Agregar</button></h5>
                                <div class="columna">
                                </div>
                            </div>
                        </div>

                      </div>
                      <div hidden="true" id="cuerpo_linkeado">
                        <!-- comienzo seccdio invididual -->
                        <div class="row">
                          <h5>Nuevo Pozo:  <button id="btn-agregarPozo" class="btn btn-success  " type="button"><i class="fa fa-fw fa-plus"></i> Agregar</button></h5>
                        </div>

                       <div id="contenedorPozos" class="">


                       </div>
                        <!-- fin despaleable pozos-->
                      </div>

                  </div> <!-- /Fin panel minimizable -->

                  <div class="modal-footer">
                    <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">ACEPTAR</button>
                    <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                    <input type="hidden" id="id_progresivo" value="0">
                  </div>

                </div> <!-- Fin modal-header -->

            </div>
          </div>
    </div>

    <!-- Modal Progresivo Individual -->
    
    <div class="modal fade" id="modalProgInd" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-keyboard="false" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header" style="background: #5cb85c;">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title" style="color: #fff;">| NUEVO PROGRESIVO INDIVIDUAL</h3>
                </div>

                <div  id="colapsado" class="collapse in">
                  <div class="modal-body modal-Cuerpo">
                      <div class="row">
                        <div class="col-md-6 col-lg-6">
                          <h5>Nombre Progresivo</h5>
                          <input id="nombre_progresivo_ind" type="text" class="form-control" placeholder="Nombre Progresivo" autocomplete="off">
                          <br>
                          <!-- <div id="alerta-nombre_progresivo" class="alert alert-danger"><span></span></div> -->
                          <span id="alerta-nombre-progresivo" class="alertaSpan"></span>
                        </div>
                        <div class="col-xs-6 col-md-6 col-lg-6">
                          <h5>Valor Máximo</h5>
                          <input id="maximo_ind" type="text" class="form-control" placeholder="Valor Máximo">
                        </div>
                      </div>
                      <br>
                      <div id="cuerpo_individual">
                        <div class="row">
                          <div class="col-xs-6 col-md-6 col-lg-6">
                            

                            <h5>Buscador Islas <i class="fa fa-fw fa-search"></i></h5>
                            <div class="row">
                              <div class="input-group lista-datos-group">
                                              <input id="inputIslaInd" class="form-control" type="text" value="" autocomplete="off" >
                                              <span class="input-group-btn">
                                                <button class="btn btn-default btn-lista-datos agregarIsla" type="button"><i class="fa fa-plus"></i></button>
                                              </span>
                              </div>

                            </div>
                            <br>
                            <h5>Buscador Maquinas <i class="fa fa-fw fa-search"></i></h5>
                            <div class="row">
                              <div class="input-group lista-datos-group">
                                              <input id="inputMtmInd" class="form-control" type="text" value="" autocomplete="off" >
                                              <span class="input-group-btn">
                                                <button class="btn btn-default btn-lista-datos agregarMaquina" type="button"><i class="fa fa-plus"></i></button>
                                              </span>
                              </div>
                            </div>

                          </div>

                          <div id="" class="col-md-6 col-lg-6">
                            <h5>Maquinas Seleccionadas:</h5>
                            <ul class="listaMaquinas">
                            </ul>
                          </div>
                        </div>
                        <br>
                        <div class="row" id="niveles_ind">
                            <div class="col-lg-12">
                                 <button class="btn btn-success btn-agregarNivelProgresivo" type="button"><i class="fa fa-fw fa-plus"></i> Nuevo Nivel de Progresivo</button>
                                <div class="columna" style="padding-top: 15px;">
                                </div>
                            </div>
                        </div>

                      </div>

                  </div> <!-- /Fin panel minimizable -->

                  <div class="modal-footer">
                    <button type="button" class="btn btn-successAceptar" id="btn-guardar-ind" value="nuevo">ACEPTAR</button>
                    <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                    <input type="hidden" id="id_progresivo_ind" value="0">
                  </div>

                </div> <!-- Fin modal-header -->

            </div>
          </div>
    </div>
    <!-- Fin modal Progresivo Individual -->


    <!-- Modal Progresivo Linkeado -->

    <div class="modal fade" id="modalProgLink" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-keyboard="false" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header" style="background: #5cb85c;">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title" style="color: #fff;">| NUEVO PROGRESIVO LINKEADO</h3>
                </div>

                <div  id="colapsado" class="collapse in">
                  <div class="modal-body modal-Cuerpo">
                      <div class="row">
                        <div class="col-md-6 col-lg-6">
                          <h5>Nombre Progresivo</h5>
                          <input id="nombre_progresivo_link" type="text" class="form-control" placeholder="Nombre Progresivo" autocomplete="off">
                          <br>
                          <!-- <div id="alerta-nombre_progresivo" class="alert alert-danger"><span></span></div> -->
                          <span id="alerta-nombre-progresivo" class="alertaSpan"></span>
                        </div>
                        <div class="col-xs-6 col-md-6 col-lg-6">
                          <h5>Valor Máximo</h5>
                          <input id="maximo_link" type="text" class="form-control" placeholder="Valor Máximo">
                        </div>
                      </div>
                      
                      <div id="cuerpo_individual">
                        
                        
                        <div class="row" id="niveles_link">
                            <div class="col-lg-12">
                              <button class="btn btn-success btn-agregarNivelProgresivo" type="button"><i class="fa fa-fw fa-plus"></i> Nuevo Nivel de Progresivo</button>
                              <div class="columna" style="padding-top: 15px;">
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row" style="border-top: 4px solid #a0968b; padding-top: 15px;"></div>

                        <div id="cuerpo_linkeado">
                          <!-- comienzo seccdio invididual -->
                          <div class="row">

                              <div class="row">
                                  <div class="col-xs-6 col-md-6 col-lg-6">
                                    
        
                                    <h5>Buscador Islas <i class="fa fa-fw fa-search"></i></h5>
                                    <div class="row">
                                      <div class="input-group lista-datos-group">
                                                      <input id="inputIslaLink" class="form-control" type="text" value="" autocomplete="off" >
                                                      <span class="input-group-btn">
                                                        <button class="btn btn-default btn-lista-datos agregarIsla" type="button"><i class="fa fa-plus"></i></button>
                                                      </span>
                                      </div>
        
                                    </div>
                                    <br>
                                    <h5>Buscador Maquinas <i class="fa fa-fw fa-search"></i></h5>
                                    <div class="row">
                                      <div class="input-group lista-datos-group">
                                                      <input id="inputMtmLink" class="form-control" type="text" value="" autocomplete="off" >
                                                      <span class="input-group-btn">
                                                        <button class="btn btn-default btn-lista-datos agregarMaquina" type="button"><i class="fa fa-plus"></i></button>
                                                      </span>
                                      </div>
                                    </div>
        
                                  </div>
        
                                  <div id="" class="col-md-6 col-lg-6">
                                    <h5>Maquinas Seleccionadas:</h5>
                                    <ul class="listaMaquinas">
                                    </ul>
                                  </div>
                                </div>

                            <div class="col-lg-12">
                               <button id="btn-agregarPozo-link" class="btn btn-success  " type="button"><i class="fa fa-fw fa-plus"></i> Nuevo Pozo</button>
                            </div>
                          </div>
                          <br>
  
                         <div id="contenedorPozosLink" class="">
  
  
                         </div>
                          <!-- fin despaleable pozos-->
                        </div>




                      </div>




                  </div> <!-- /Fin panel minimizable -->

                  <div class="modal-footer">
                    <button type="button" class="btn btn-successAceptar" id="btn-guardar-link" value="nuevo">ACEPTAR</button>
                    <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                    <input type="hidden" id="id_progresivo_link" value="0">
                  </div>

                </div> <!-- Fin modal-header -->

            </div>
          </div>
    </div>




    <!-- Fin Modal Progresivo Linkeado -->




    <!-- Modal Eliminar -->
    <div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-titleEliminar" id="myModalLabel">ADVERTENCIA</h3>
                </div>

                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                  <form id="frmEliminar" name="frmProgresivo" class="form-horizontal" novalidate="">
                      <div class="form-group error ">
                          <div class="col-xs-12">
                            <strong>¿Seguro desea eliminar el PROGRESIVO?</strong>
                          </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-dangerEliminar" id="btn-eliminarModal" value="0">ELIMINAR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
    </div>

    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| PROGRESIVOS</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Progresivos</h5>
      <p>
        Se podrán cargar los distintos tipos de progresivos asociados a sus respectivas tragamonedas.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="js/paginacion.js" charset="utf-8"></script>
    <script src="/js/lista-datos.js" type="text/javascript"></script>
    <!-- JavaScript personalizado -->
    <script src="js/seccionProgresivos.js" charset="utf-8"></script>
    @endsection
