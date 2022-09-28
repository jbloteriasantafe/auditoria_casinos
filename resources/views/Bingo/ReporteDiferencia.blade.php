@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
<?php
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;

$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];;
$casinos = $usuario->casinos;
?>

@section('estilos')
  <link rel="stylesheet" href="/css/paginacion.css">
  <link rel="stylesheet" href="/css/lista-datos.css">

  <link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
  <link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
  <link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
  <link rel="stylesheet" href="/css/animacionCarga.css">

@endsection

@section('contenidoVista')
<style media="screen">
#cuerpoTabla tr i{
  color: #FF1744
}

.pintar-red{
  color: rgb(239, 83, 80);
}
.pintar-orange{
  color: rgb(255, 167, 38);
}
</style>
                <div class="row">
                  <div class="col-lg-12 col-xl-12">

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
                                    <h5>Fecha de la sesión</h5>
                                    <!-- <div class="form-group"> -->
                                       <div class='input-group date' id='dtpBuscadorFecha' data-link-field="buscadorFecha" data-link-format="yyyy-mm-dd">
                                           <input type='text' class="form-control" placeholder="Fecha de sesión" id="buscadorFecha"/>
                                           <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                           <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                       </div>
                                       <input class="form-control" type="hidden" id="buscadorFecha" value=""/>
                                    <!-- </div> -->
                                  </div>
                                  <div class="col-md-3">
                                    <h5>CASINO</h5>
                                    <select id="buscadorCasino" class="form-control selectCasinos" name="">
                                        <option value="0">-Todos los Casinos-</option>
                                        @foreach($casinos as $casino)
                                        <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                        @endforeach
                                    </select>
                                  </div>

                                  <div class="col-md-6 text-right">
                                    <h5 style="color:#f5f5f5;">boton buscar</h5>
                                    <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                                  </div>
                                </div><br>
                              </div> <!-- /.panel-body -->
                            </div>
                          </div> <!-- /.panel -->
                        </div> <!-- /.col-md-12 -->
                    </div> <!-- Fin de la fila de FILTROS -->


                      <div class="row"><!-- RESULTADOS BÚSQUEDA -->
                        <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>LISTA DE SESIONES</h4>
                            </div>
                            <div class="panel-body modal-cuerpo">
                              <table id="tablaResultados" class="table table-striped tablesorter">
                                <thead>
                                  <tr>
                                    <th class="col" value="fecha_sesion">FECHA SESIÓN <i class="fa fa-sort"></i></th>
                                    <th class="col" value="casino">CASINO <i class="fa fa-sort"></i></th>
                                    <th class="col" value="hora_inicio">HORA INICIO </th>
                                    <th class="col" value="importacion">IMPORTADO <i class="fa fa-sort"></i></th>
                                    <th class="col" value="relevamiento">RELEVAMIENTO <i class="fa fa-sort"></i></th>
                                    <th class="col" value="sesion_cerrada">SESIÓN CERRADA <i class="fa fa-sort"></i></th>
                                    <th class="col" value="visado">VISADO <i class="fa fa-sort"></i></th>
                                    <th class="col" >ACCIÓN</th>
                                  </tr>
                                </thead>
                                <tbody id="cuerpoTabla">


                                </tbody>
                              </table>
                              <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                              </div>
                            </div>
                          </div>



                        </div> <!-- Fin del col de los filtros -->

                      </div> <!-- Fin del row de la tabla -->

            </div> <!--/columna row -->

            <!-- Modal DETALLES DIFERENCIA -->
            <div class="modal fade" id="modalDetalles" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-lg" style="min-width:85%;">
                     <div class="modal-content">
                        <div class="modal-header pbzero">
                          <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                          <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                          <h3 class="modal-title pbtitle" id="myModalLabel">| DETALLES DIFERENCIA</h3>
                        </div>

                        <div  id="colapsado" class="collapse in">
                         <div class="modal-body modal-Cuerpo">
                          <form id="frmDetalles" name="frmDetalles" class="form-horizontal" novalidate="">
                              <div class="form-group error">
                                <div class="tab-content">
                                  <div class="col-lg-12 tab-pane fade in active" id="detalles">
                                    <div id="columnaDetalles" class="row">
                                      <div id="terminoDatos" class="row" style="margin-bottom: 15px;">
                                        <div class="col-lg-12">
                                        <h6>DATOS DE LA SESIÓN</h6>
                                        </div>

                                        <div class="col-lg-3">
                                          <h5>POZO DOTACIÓN INICIAL</h5>
                                          <input id="pozo_dotacion_inicial_d" name="pozo_dotacion_inicial_d" type="text" class="form-control"  placeholder="" value="">
                                        </div>

                                        <div class="col-lg-3">
                                          <h5>POZO EXTRA INICIAL</h5>
                                          <input id="pozo_extra_inicial_d" name="pozo_extra_inicial_d" type="text" class="form-control"  placeholder="" value="">
                                        </div>

                                        <div class="col-lg-3">
                                          <h5>POZO DOTACIÓN FINAL</h5>
                                          <input id="pozo_dotacion_final_d" name="pozo_dotacion_final_d" type="text" class="form-control"  placeholder="" value="">
                                        </div>

                                        <div class="col-lg-3">
                                          <h5>POZO EXTRA FINAL</h5>
                                          <input id="pozo_extra_final_d" name="pozo_extra_final_d" type="text" class="form-control"  placeholder="" value="">
                                        </div>
                                    </div>


                                      <div class="row">
                                        <div class="col-lg-12">
                                        <h6>DETALLES DE LA SESIÓN</h6>
                                        </div>
                                        <div class="col-lg-2">
                                          <h5>VALOR CARTON</h5>
                                        </div>
                                        <div class="col-lg-2">
                                          <h5>SERIE INICIAL</h5>
                                        </div>
                                        <div class="col-lg-2">
                                          <h5>CARTON INICIAL</h5>
                                        </div>
                                        <div class="col-lg-2">
                                          <h5>SERIE FINAL</h5>
                                        </div>
                                        <div class="col-lg-2">
                                          <h5>CARTON FINAL</h5>
                                        </div>
                                      </div>

                                      <div id="terminoDatos2" class="row" style="margin-bottom: 15px;">
                                      </div>
                                  </div>
                                  <div class="col-lg-12">
                                    <h6>RELEVAMIENTOS CARGADOS VS IMPORTADOS</h6>
                                  </div>
                                  <span id="alerta_sesion" class="alertaSpan"></span>


                                  <div class="panel-body modal-cuerpo">
                                    <table id="tablaResultadosDetalles" class="table table-striped">
                                      <thead>
                                        <tr>
                                          <th class="col" value="nro_partida">PARTIDA</th>
                                          <th class="col" value="hora_sesion">HORA</th>
                                          <th class="col" value="serie_inicial">SERIE INICIAL</th>
                                          <th class="col" value="carton_inicial">CARTON INICIAL</th>
                                          <th class="col" value="carton_final">CARTON FINAL</th>
                                          <th class="col" value="serie_final">SERIE FINAL</th>
                                          <th class="col" value="carton_inicial">CARTON INICIAL</th>
                                          <th class="col" value="carton_final">CARTON FINAL</th>
                                          <th class="col" value="cartones_vendidos">CARTONES VENDIDOS</th>
                                          <th class="col" value="valor_carton">VALOR CARTON</th>
                                          <th class="col" value="cant_bola">CANT. BOLA</th>
                                          <th class="col" value="recaudado">RECAUDADO</th>
                                          <th class="col" value="premio_bingo">PREMIO LÍNEA</th>
                                          <th class="col" value="premio_bingo">PREMIO BINGO</th>
                                          <th class="col" value="pozo_dot">POZO DOT.</th>
                                          <th class="col" value="pozo_extra">POZO EXTRA</th>
                                          <th class="col" value="estado"></th>
                                        </tr>
                                      </thead>
                                      <tbody id="cuerpoTablaDetalles">


                                      </tbody>
                                    </table>
                                    </div>


                                  </div>

                                  <div class="col-md-8 col-md-offset-2">
                                    <h5>OBSERVACIONES DE VISADO</h5>
                                  <textarea id="observacion_validacion" class="form-control" style="resize:vertical;"></textarea>
                                </div>

                                </div>
                              </div>
                              </div>

                          </form>
                        </div>

                        <div class="modal-footer">
                          <button type="button" class="btn btn-successAceptar" id="btn-finalizarValidacion" value="nuevo">VISAR SESIÓN</button>

                          <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                          <input type="hidden" id="id_importacion" value="0">
                        </div>
                      </div>
                    </div>
                  </div>
            </div>

            <!-- Modal ERROR -->
            <div class="modal fade" id="modalError" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                  <div class="modal-dialog">
                     <div class="modal-content">
                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                          <h3 class="modal-title-error" id="myModalLabel">ERROR</h3>
                        </div>

                        <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                              <div class="form-group error ">
                                  <div class="col-lg-12">
                                    <strong id="errorNoImportada"></strong>
                                  </div>
                              </div>

                        </div>

                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">ACEPTAR</button>
                        </div>
                    </div>
                  </div>
            </div>





    <!-- token -->
    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title2" style="color: #fff;">| IMPORTAR RELVAMIENTOS DE PARTIDAS</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Importar Relevamientos</h5>
      <p>
        Visualiza los estados de las sesiones y las valida.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="/js/paginacion.js" charset="utf-8"></script>
    <!-- JavaScript personalizado -->
    <script src="/js/Bingo/gestion.js" charset="utf-8"></script>
    <script src="/js/Bingo/reporteDiferencia.js" type="text/javascript"></script>


    <!-- Custom input Bootstrap -->
    <script src="/js/fileinput.min.js" type="text/javascript"></script>
    <script src="/js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>
    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>


    @endsection
