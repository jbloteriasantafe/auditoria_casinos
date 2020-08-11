@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

<?php
use Illuminate\Http\Request;
use App\Http\Controllers\UsuarioController;
use\App\http\Controllers\RelevamientoAmbientalController;
?>

@section('estilos')
<link rel="stylesheet" href="/css/paginacion.css">
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/animacionCarga.css">


<!--plugins que voy probando -->
<link href='http://fonts.googleapis.com/css?family=Lato' rel='stylesheet' type='text/css'>
<link rel="stylesheet" type="text/css" href="/css/screen.css" media="screen" />

<style>
.page {
  display: none;
}
.active {
  display: inherit;
}
.easy-autocomplete{
width:initial!important
}

/* Make circles that indicate the steps of the form: */
.step {
height: 15px;
width: 15px;
margin: 0 2px;
background-color: #bbbbbb;
border: none;
border-radius: 50%;
display: inline-block;
opacity: 0.5;
}

/* Mark the active step: */
.step.actived {
opacity: 1;
}

/* Mark the steps that are finished and valid: */
.step.finish {
background-color: #4CAF50;
}

#gallery {
  width: 720px;
}

</style>
@endsection

@section('contenidoVista')

    <div class="col-xl-9">

        <!-- FILTROS DE BÚSQUEDA -->
        <div class="row">
            <div class="col-md-12">
                <div id="contenedorFiltros" class="panel panel-default" >
                  <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                    <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
                  </div>
                  <div id="collapseFiltros" class="panel-collapse collapse">
                    <div class="panel-body">
                      <div class="row">
                        <div class="col-md-4">
                            <h5>Apellido</h5>
                            <input class="form-control" id="buscadorApellido" value=""/>
                        </div>
                        <div class="col-md-4">
                            <h5>DNI</h5>
                            <input class="form-control" id="buscadorDni" value="{{$dni}}"/>
                        </div>
                        <div class="col-md-4">
                            <h5>Casino</h5>
                            <select id="buscadorCasino" class="form-control selectCasinos" name="">
                                <option value="0">-Todos los Casinos-</option>
                                @foreach ($casinos as $casino)
                                  <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                @endforeach
                            </select>
                        </div>
                      </div><br>

                      <div class="row">
                        <center>
                          <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                        </center>
                      </div>
                      <br>
                    </div>
                  </div>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-md-12">
                <div class="col-md-8" id="galeria"  hidden>
                  <div id="viewer" class="row" style="background: rgba(0,0,0,0.2);height: 600px;display: flex;justify-content: center;align-items: center;">
                  </div>
                  <br>
                  <div class="row" style="background: rgba(0,0,0,0.2);height: 200px;">
                    <div class="row">
                      <h4 class="col-md-4 col-md-offset-4" style="text-align: center;">
                        <button id="prev" type="button" class="btn btn-link"><i class="fas fa-arrow-left"></i></button>
                        Página <span id="currpage">0</span> de <span id="pages">0</span>
                        <button id="next" type="button" class="btn btn-link"><i class="fas fa-arrow-right"></i></button>
                      </h4>
                    </div>
                    <div id="thumbs">
                      <div class="col-md-1"></div>
                      <div class="col-md-2" style="text-align: center;" data-n="1"></div>
                      <div class="col-md-2" style="text-align: center;" data-n="2"></div>
                      <div class="col-md-2" style="text-align: center;" data-n="3"></div>
                      <div class="col-md-2" style="text-align: center;" data-n="4"></div>
                      <div class="col-md-2" style="text-align: center;" data-n="5"></div>
                      <div class="col-md-1"></div>
                    </div>
                  </div>
                </div>

                <!-- Detalles del AE seleccionado -->
                <div class="col-md-4" style="float:right">
                  <div class="panel panel-default">

                      <div class="panel-heading">
                        <h4>DETALLES DEL AE SELECCIONADO</h4>
                      </div>

                      <div class="panel-body">
                        <div class="row">
                          <div class="col-lg-12">
                            <h5 style="display:inline-block">APELLIDO </h5>
                            <span id="apellido" style="margin-top:8px; margin-left: 15px;"></span><br>
                            <h5 style="display:inline-block">NOMBRES </h5>
                            <span id="nombres" style="margin-top:6px; margin-left: 15px;"></span><br>
                            <h5 style="display:inline-block">DNI </h5>
                            <span id="dni" style="margin-top:6px; margin-left: 15px;"></span><br>
                            <h5 style="display:inline-block">CASINO </h5>
                            <span id="casino" style="margin-top:6px; margin-left: 15px;"></span><br>
                            <h5 style="display:inline-block">ESTADO </h5>
                            <span id="estado" style="margin-top:6px; margin-left: 15px;"></span><br>
                            <h5 style="display:inline-block">FECHA AE </h5>
                            <span id="fecha_ae" style="margin-top:6px; margin-left: 15px;"></span><br>
                            <h5 style="display:inline-block">VENCIMIENTO 1° PERÍODO </h5>
                            <span id="vencimiento" style="margin-top:6px; margin-left: 15px;"></span><br>
                            <h5 style="display:inline-block">FECHA REVOCACIÓN </h5>
                            <span id="fecha_revocacion" style="margin-top:6px; margin-left: 15px;"></span><br>
                            <h5 style="display:inline-block">FECHA CIERRE </h5>
                            <span id="fecha_cierre" style="margin-top:6px; margin-left: 15px;"></span>
                            <br>
                          </div>
                        </div>
                      </div> <!-- panel-body -->
                  </div> <!-- panel -->
                </div>

          </div>
        </div>

    </div> <!-- row principal -->


  <!-- token -->
  <meta name="_token" content="{!! csrf_token() !!}" />
  @endsection


  <!-- Comienza modal de ayuda -->
  @section('tituloDeAyuda')
  <h3 class="modal-title2" style="color: #fff;">| SESIONES</h3>
  @endsection
  @section('contenidoAyuda')
  <div class="col-md-12">
    <h5>Tarjeta de Sesiones</h5>
    <p>
      Agregar nuevos autoexluidos, revocar autoexclusiones, ver listado y estados.
  </div>
  @endsection
  <!-- Termina modal de ayuda -->


  @section('scripts')
  <!-- JavaScript paginacion -->
  <script src="/js/paginacion.js" charset="utf-8"></script>
  <!-- JavaScript personalizado -->


  <!-- Custom input Bootstrap -->
  <script src="/js/fileinput.min.js" type="text/javascript"></script>
  <script src="/js/locales/es.js" type="text/javascript"></script>
  <script src="/themes/explorer/theme.js" type="text/javascript"></script>
  <!-- DateTimePicker JavaScript -->
  <script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
  <script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

  <script type="text/javascript" src="/js/jquery.jfollow.js"></script>
  <script type="text/javascript" src="/js/jquery.imagesloaded.min.js"></script>
  <script type="text/javascript" src="/js/jquery.ImageGallery.js"></script>

  <script src="/js/Autoexclusion/galeriaImagenesAutoexcluidos.js" charset="utf-8"></script>
  @endsection
