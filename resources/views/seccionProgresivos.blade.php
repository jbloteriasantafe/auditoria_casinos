@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
<?php
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;

$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
$selected = False;//Se setea una sola vez, pone el atributo selected en el casino del modal
?>


@section('estilos')
<link rel="stylesheet" href="css/paginacion.css"/>
<link rel="stylesheet" href="/css/lista-datos.css"/>
<style>
  .chico {
    font-size: 85%;
  }
  .chico2 {
    font-size: 95%;
  }
  .input_chico {
    width: 90%;
  }
  .sinflechas{
    -webkit-appearance: none;
    margin: 0;
    -moz-appearance: textfield;
  }
  .erroneo {
    border-color : #dc3545;
  }
</style>
@endsection

@section('contenidoVista')

<datalist id='maquinas_lista'>
</datalist>

<div class="row">
  <div class="col-xl-3">
    <div class="row">
      <div class="col-xl-12 col-md-4"> <!-- BOTON NUEVO PROG INDIVIDUAL -->
            <a href="" id="btn-nuevo-ind" style="text-decoration: none;">
              <div class="panel panel-default panelBotonNuevo">
                <center><img class="imgNuevo" src="/img/logos/progresivos_white.png"></center>
                  <div class="backgroundNuevo">
                  </div>
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

      <div class="col-xl-12 col-md-4"> <!-- BOTON NUEVO PROG INDIVIDUAL -->
          <a href="" id="btn-nuevo" style="text-decoration: none;">
            <div class="panel panel-default panelBotonNuevo">
              <center><img class="imgNuevo" src="/img/logos/progresivos_white.png"></center>
                <div class="backgroundNuevo">
                </div>
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
  <div class="col-xl-9">
    <div id="contenedorFiltrosIndividuales" class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4>Buscar/modificar individuales</h4>
          </div>
          <div class="">
            <div class="panel-body">
              <div class="row"> <!-- Primera fila -->
                <div class="col-lg-3">
                  <h5>Casino</h5>
                  <select class="form-control" id="busqueda_casino_individuales">
                    @foreach ($usuario['usuario']->casinos as $casino)
                    <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-lg-3">
                  <h5>Desde</h5>
                  <input id="maquina_desde" type="text" class="form-control" placeholder="Número maquina">
                </div>
                <div class="col-lg-3">
                  <h5>Hasta</h5>
                  <input id="maquina_hasta" type="text" class="form-control" placeholder="Número maquina">
                </div>
                <div class="col-lg-3">
                  <h5>Búsqueda</h5>
                  <button id="btn-buscar-individuales" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <div id="contenedorFiltros" class="row"> <!-- Tarjeta de FILTROS -->
          <div class="col-md-12">
            <div class="panel panel-default">
              <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                <h4>Filtros de búsqueda linkeados <i class="fa fa-fw fa-angle-down"></i></h4>
              </div>
              <div id="collapseFiltros" class="panel-collapse collapse">
                <div class="panel-body">
                  <div class="row"> <!-- Primera fila -->
                    <div class="col-lg-3">
                      <h5>Casino</h5>
                      <select class="form-control" id="busqueda_casino">
                        @if ($usuario['usuario']->es_superusuario)
                        <option value="0">Todos los casinos</option>
                        @endif
                        @foreach ($usuario['usuario']->casinos as $casino)
                        <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-lg-3">
                      <h5>Nombre Progresivo</h5>
                      <input id="B_nombre_progresivo" type="text" class="form-control" placeholder="Nombre progresivo">
                    </div>
                    <div class="col-lg-3">
                      <h5>Islas</h5>
                      <input id="B_islas" type="test" class="form-control" placeholder="Islas">
                    </div>
                    <div class="col-lg-3">
                      <h5>Búsqueda</h5>
                      <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                    </div>
                  </div>
                </div> <!-- / Primera fila -->
              </div>
            </div>
          </div>
        </div>
      </div> <!-- / Tarjeta FILTROS -->
    </div>
    <div class="row"> 
      <div class="col-md-12">
        <div class="panel panel-default">
        <div class="panel-heading">
          <h4>ÚLTIMOS PROGRESIVOS LINKEADOS</h4>
        </div>
        <div class="panel-body">
        <table id="tablaResultados" class="table table-fixed tablesorter">
          <thead>
            <tr>
              <th class="col-xs-3" value="progresivo.nombre" estado="">NOMBRE PROGRESIVO<i class="fa fa-sort"></i></th>
              <th class="col-xs-3" value="progresivo.id_casino" estado="">CASINO<i class="fa fa-sort"></i></th>
              <th class="col-xs-3" value="progresivo.islas">ISLAS</td>
              <th class="col-xs-3">ACCIONES</th>
            </tr>
          </thead>
          <tbody id="cuerpoTabla" style="height: 350px;">
            <tr class="filaEjemplo" style='display: none;'>
              <td class="col-xs-3 nombre">PROGRESIVO999</td>
              <td class="col-xs-3 casino">CASINO999</td>
              <td class="col-xs-3 islas">ISLA1/ISLA2/...</td>
              <td class="col-xs-3 acciones">
                <button class="btn btn-info detalle">
                  <i class="fa fa-fw fa-search-plus"></i>
                </button>
                <span> </span>
                <button class="btn btn-info modificar">
                  <i class="fa fa-fw fa-pencil-alt"></i>
                </button>
                <span> </span>
                <button class="btn btn-info eliminar">
                  <i class="fa fa-fw fa-trash-alt"></i>
                </button>
              </td>
            </tr>
            </tbody>
          </table>
          <!--Comienzo indices paginacion-->
          <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
        </div>
      </div>
    </div>
</div>

<!-- Modal Progresivo -->
<div class="modal fade" id="modalProgresivo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-keyboard="false" aria-hidden="true">
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
            <div class="col-md-4 col-lg-4">
              <h5 class='row'>Nombre Progresivo</h5>
              <input id="nombre_progresivo" type="text" class="form-control" placeholder="Nombre Progresivo" autocomplete="off">
            </div>
            <div class="col-md-4 col-lg-4">
              <h5 class='row'>Porcentaje de recuperación</h5>
              <input id="porc_recup" type="text"  class="form-control" placeholder="0">
            </div>
            <div class="col-md-4 col-lg-4">
              <h5 class='row'>Casino</h5>
              <select class="form-control" id="modalProgresivo_casino">
                @foreach ($usuario['usuario']->casinos as $casino)
                <option value="{{$casino->id_casino}}" <?php if(!$selected){echo("selected");$selected=True;}?> >{{$casino->nombre}}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div id='modalProgresivo_cuerpo' class='row'>
            <hr>
            <div class=''>
              <h3 class=''>Pozos</h3>
              <div class="row">
                <h5>Nuevo Pozo:
                  <button id="btn-agregarPozo" class="btn btn-success  " type="button">
                    <i class="fa fa-fw fa-plus"></i>
                    <b>Agregar</b>
                  </button>
                </h5>
              </div>
              <div id="contenedorPozos" class="row" style="overflow-y: auto;overflow-x: hidden;height: 400px;"></div>
            </div>
            <hr>
            <div class=''>
              <h3 class=''>Maquinas</h3>
              <div class="row">
                <h5>Enlazar maquina:
                  <button id='btn-agregarMaquina' class="btn btn-success">
                    <i class="fa fa-fw fa-plus"></i>
                    <b>Enlazar</b>
                  </button>
                </h5>
              </div>
              <div id="contenedorMaquinas" class="row" style="overflow-y: auto;overflow-x: hidden;height: 400px;"></div>
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
</div>


<div class="modal fade" id="modalProgresivoIndividual" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-keyboard="false" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background: #5cb85c;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title" style="color: #fff;">| NUEVOS PROGRESIVOS INDIVIDUALES</h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body modal-Cuerpo">
          <div class="" id='modalProgresivoIndividual_seccionSup'>
            <div class="row">
              <h3 class='col-md-3 col-lg-3'>Parametros base</h3>
              <div class='col-md-5 col-lg-5'></div>
              <h5 class='col-md-1 col-lg-1'>Casino</h5>
              <div class='col-md-3 col-lg-3 '>
                <select class="form-control" id="modalProgresivoIndividual_casino">
                  @foreach ($usuario['usuario']->casinos as $casino)
                  <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="row no-gutters" id='modalProgresivoIndividual_seccionParametros'>
            <div class="col-md-3 col-lg-3">
              <h5 class="">% Recuperación</h5>
              <input id="inputPorcRecupIndividual" class="editable form-control" type="text">
            </div>
            <div class="col-md-3 col-lg-3">
              <h5 class=''>Máximo</h5>
              <input id="inputMaximoIndividual" class="editable form-control" type="text">
            </div>
            <div class="col-md-2 col-lg-2">
              <h5 class=''>Base</h5>
              <input id="inputBaseIndividual" class="editable form-control" type="text">
            </div>
            <div class="col-md-2 col-lg-2">
              <h5 class=''>% Visible</h5>
              <input id="inputPorcVisibleIndividual" class="editable form-control" type="text">
            </div>
            <div class="col-md-2 col-lg-2">
              <h5 class=''>% Oculto</h5>
              <input id="inputPorcOcultoIndividual" class="editable form-control" type="text">
            </div>
          </div>
          <div class="row">
            <div class=''>
              <h3 class=''>Maquinas</h3>
              <div class="row">
                <h5>Agregar progresivo individual:
                  <button id='btn-agregarMaquinaIndividual' class="btn btn-success">
                    <i class="fa fa-fw fa-plus"></i>
                    <b>Agregar</b>
                  </button>
                </h5>
              </div>
              <div id="contenedorMaquinasIndividual" class="row" style="overflow-y: auto;overflow-x: hidden;height: 400px;"></div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-successAceptar" id="btn-guardarIndividual" value="nuevo">ACEPTAR</button>
            <button type="button" class="btn btn-default" id="btn-cancelarIndividual" data-dismiss="modal" aria-label="Close">CANCELAR</button>
            <input type="hidden" id="id_progresivo" value="0">
          </div>
        </div> <!-- Fin modal-header -->
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



<!-- Tabla del pozo de ejemplo -->
<div class="row top-buffer tablaPozoDiv ejemplo" style="display: none">
  <div class="col-md-14">
    <div>
      <button class="btn btn-link abrirPozo"></i>
        <i class="fa fa-fw fa-angle-up"></i>
      </button>
      <b class="nombrePozo">Pozo</b>
      <button class="btn btn-link editarPozo">
        <i class="fa fa-fw fa-pencil-alt"></i>
      </button>
      <button class="btn btn-link eliminarPozo"></i>
        <i class="fa fa-fw fa-trash-alt"></i>
      </button>

      <div class="panel-body collapse">
        <table class="table table-condensed tablesorter tablaPozo" style="padding: 1px;">
          <thead>
            <tr>
              <th class="col-xs-1">Mover</th>
              <th class="col-xs-1" value="nivel_progresivo.nro_nivel" estado="">#<i class="fa fa-sort"></i></th>
              <th class="col-xs-2" value="nivel_progresivo.nombre_nivel" estado="">Nombre nivel progresivo<i class="fa fa-sort"></i></th>
              <th class="col-xs-2" value="nivel_progresivo.base" estado="">Base<i class="fa fa-sort"></i></th>
              <th class="col-xs-2" value="nivel_progresivo.maximo" estado="">Máximo<i class="fa fa-sort"></i></th>
              <th class="col-xs-2" value="nivel_progresivo.porc_visible" estado="">% Visible<i class="fa fa-sort"></i></th>
              <th class="col-xs-2" value="nivel_progresivo.porc_oculto" estado="">% Oculto<i class="fa fa-sort"></i></th>
              <th class="col-xs-2">ACCIONES</th>
            </tr>
          </thead>
          <tbody class="cuerpoTablaPozo">
            <tr class="filaEjemplo" style="display: none;" >
              <td class="col-xs-1 cuerpoTablaPozoFlechas">
                <button class="btn btn-link subir chico" style="background-color: transparent !important;" >
                  <i class="fa fa-fw fa-arrow-up"></i>
                </button>
                <button class="btn btn-link bajar chico" style="background-color: transparent !important;" >
                  <i class="fa fa-fw fa-arrow-down"></i>
                </button>
              </td>
              <td class="col-xs-1 cuerpoTablaPozoNumero">999</td>
              <td class="col-xs-2 cuerpoTablaPozoNombre">NOMBRE EJEMPLO</td>
              <td class="col-xs-2 cuerpoTablaPozoBase">999999999</td>
              <td class="col-xs-2 cuerpoTablaPozoMaximo">999999999</td>
              <td class="col-xs-1 cuerpoTablaPorcVisible">12.34</td>
              <td class="col-xs-1 cuerpoTablaPorcOculto">12.34</td>
              <td class="col-xs-2 cuerpoTablaPozoAcciones">
                <button class="btn btn-info editar">
                  <i class="fa fa-fw fa-pencil-alt"></i>
                </button>
                <button class="btn btn-info borrar">
                  <i class="fa fa-fw fa-trash-alt"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <button class="btn btn-info agregar">
          <i class="fa fa-fw fa-plus"></i>
          <b>Agregar nivel</b>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="row top-buffer tablaMaquinasDiv ejemplo"  style="display: none;">
  <div class="col-md-10">
    <div>
      <div class="panel-body">
        <table class="table table-condensed tablesorter tablaMaquinas">
          <thead>
            <tr>
              <th class="col-xs-1" value="maquina.nro_admin" estado="">#<i class="fa fa-sort"></i></th>
              <th class="col-xs-2" value="maquina.sector" estado="">Sector<i class="fa fa-sort"></i></th>
              <th class="col-xs-1" value="maquina.isla" estado="">Isla<i class="fa fa-sort"></i></th>
              <th class="col-xs-2" value="maquina.marca_juego" estado="">Marca juego<i class="fa fa-sort"></i></th>
              <th class="col-xs-4">ACCIONES</th>
            </tr>
          </thead>
          <tbody class="cuerpoTabla" style="overflow-y: auto;overflow-x: hidden;">
            <tr class="filaEjemplo">
              <td class="col-xs-2 cuerpoTablaNroAdmin">999</td>
              <td class="col-xs-2 cuerpoTablaSector">SECTOR999</td>
              <td class="col-xs-1 cuerpoTablaIsla">999</td>
              <td class="col-xs-4 cuerpoTablaMarcaJuego">SIN MARCA</td>
              <td class="col-xs-3 cuerpoTablaAcciones">
                <button class="btn btn-info unlink">
                  <i class="fas fa-unlink"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="row top-buffer tablaMaquinasDivIndividual ejemplo"  style="display: none;">
  <div class="col-md-12">
    <div>
      <div class="panel-body">
        <table class="table table-condensed tablesorter tablaMaquinasIndividual">
          <thead>
            <tr>
              <th class="col-xs-1 chico" value="maquina.nro_admin" estado="">
                #
                <i class="fa fa-sort"></i>
              </th>
              <th class="col-xs-1 chico" value="maquina.sector" estado="">
                Sector
                <i class="fa fa-sort"></i></th>
              <th class="col-xs-1 chico" value="maquina.isla" estado="">
                Isla
                <i class="fa fa-sort"></i>
              </th>
              <th class="col-xs-2 chico" value="maquina.marca_juego" estado="">
                Marca juego
                <i class="fa fa-sort"></i>
              </th>
              <th class="col-xs-1 chico">% Recup</th>
              <th class="col-xs-1 chico">Máximo</th>
              <th class="col-xs-1 chico">Base</th>
              <th class="col-xs-1 chico">% Visible</th>
              <th class="col-xs-1 chico">% Oculto</th>
              <th class="col-xs-2 chico">ACCIONES</th>
            </tr>
          </thead>
          <tbody class="cuerpoTabla" style="overflow-y: auto;overflow-x: hidden;">
            <tr class="filaEjemplo form-group form-group-sm">
              <td class="col-xx-1 cuerpoTablaNroAdmin chico">999</td>
              <td class="col-xs-1 cuerpoTablaSector chico">SECTOR999</td>
              <td class="col-xs-1 cuerpoTablaIsla chico">999</td>
              <td class="col-xs-2 cuerpoTablaMarcaJuego chico">SIN MARCA</td>
              <td class="col-xs-1 cuerpoPorcRecup chico">99.99</td>
              <td class="col-xs-1 cuerpoMaximo chico">999999</td>
              <td class="col-xs-1 cuerpoBase chico">9999</td>
              <td class="col-xs-1 cuerpoPorcVisible chico">99.99</td>
              <td class="col-xs-1 cuerpoPorcOculto chico">99.99</td>
              <td class="col-xs-2 cuerpoTablaAcciones">
                <button class="btn btn-info editar">
                  <i class="fas fa-pencil-alt"></i>
                </button>
                <button class="btn btn-info eliminar">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<meta name="_token" content="{!! csrf_token() !!}"/>

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
<script src="/js/paginacion.js" charset="utf-8"></script>
<script src="/js/lista-datos.js" type="text/javascript"></script>
<!-- JavaScript personalizado -->
<script src="/js/float.js" charset="utf-8"></script>
<script src="/js/seccionProgresivos.js" charset="utf-8"></script>
<script src="/js/seccionProgresivosFilas.js" charset="utf-8"></script>
@endsection
