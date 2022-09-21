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

                <div class="row">
                  <div class="col-lg-12 col-xl-12">

                     <div class="row" id="filtros"> <!-- fila de FILTROS -->
                        <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-body">
                              <div class="row">
                                <div class="col-md-3">
                                  <h5>CASINO</h5>
                                  <select id="buscadorCasino" class="form-control selectCasinos" name="">
                                    <option value="">- Todos los casinos -</option>
                                    @foreach($casinos as $casino)
                                    <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                    @endforeach
                                  </select>
                                </div>
                              </div><br>
                            </div> <!-- /.panel-body -->
                          </div> <!-- /.panel -->
                        </div> <!-- /.col-md-12 -->
                    </div> <!-- Fin de la fila de FILTROS -->
                      <div class="row"><!-- RESULTADOS BÚSQUEDA -->
                        <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>LISTA DE ESTADOS</h4>
                            </div>
                            <div class="panel-body modal-cuerpo">
                              <table id="tablaResultados" class="table table-striped tablesorter">
                                <thead>
                                  <tr>
                                    <th class="col" value="fecha_sesion">FECHA SESIÓN <i class="fa fa-sort"></i></th>
                                    <th class="col" value="casin">CASINO <i class="fa fa-sort"></i></th>
                                    <th class="col" value="importacion">IMPORTADO <i class="fa fa-sort"></i></th>
                                    <th class="col" value="relevamiento">RELEVAMIENTO <i class="fa fa-sort"></i></th>
                                    <th class="col" value="sesion_cerrada">SESIÓN CERRADA <i class="fa fa-sort"></i></th>
                                    <th class="col" value="visado">VISADO<i class="fa fa-sort"></i></th>
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


    <!-- token -->
    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title2" style="color: #fff;">| Reporte de Estados de Bingos</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <p>
        Visualiza los estados de las sesiones y las importaciones de Bingos.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="/js/paginacion.js" charset="utf-8"></script>
    <!-- JavaScript personalizado -->
    <script src="/js/Bingo/gestion.js" charset="utf-8"></script>
    <script src="/js/Bingo/reporteEstado.js" type="text/javascript"></script>
    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>
    @endsection
