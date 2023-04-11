@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('estilos')
<link rel="stylesheet" href="/css/paginacion.css"/>
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

<div class="row">
  <div class="col-xl-2">
    <div class="row">
      <a href="" id="btn-nuevo" style="text-decoration: none;">
        <div class="panel panel-default panelBotonNuevo">
          <center><img class="imgNuevo" src="/img/logos/progresivos_white.png"></center>
          <div class="backgroundNuevo"></div>
          <div class="row">
            <div class="col-xs-12">
              <center>
                <h5 class="txtLogo">+</h5>
                <h4 class="txtNuevo">NUEVO PROG</h4>
              </center>
            </div>
          </div>
        </div>
      </a>
    </div>
    <div class="row">
      <a href="" id="btn-nuevo-ind" style="text-decoration: none;">
        <div class="panel panel-default panelBotonNuevo">
          <center><img class="imgNuevo" src="/img/logos/progresivos_white.png"></center>
          <div class="backgroundNuevo"></div>
          <div class="row">
            <div class="col-xs-12">
              <center>
                <h5 class="txtLogo">+</h5>
                <h4 class="txtNuevo">CARGA MASIVA INDIV</h4>
              </center>
            </div>
          </div>
        </div>
      </a>
    </div>
  </div>
  <div class="col-xl-10">
    <div class="row">
      <div class="col-md-12">
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
                      <h5>TIPO</h5>
                      <select class="form-control" id="busqueda_tipo" form-key="es_individual" style="text-align-last: center;">
                        <option value="" selected>Todos</option>
                        <option value="0">Linkeado</option>
                        <option value="1">Individual</option>
                      </select>
                    </div>
                    <div class="col-lg-4">
                      <h5>Casino</h5>
                      <select class="form-control" id="busqueda_casino" form-key="id_casino" style="text-align-last: center;">
                        @if ($es_superusuario)
                        <option value="">Todos</option>
                        @endif
                        @foreach ($casinos as $c)
                        <option value="{{$c->id_casino}}">{{$c->nombre}}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-lg-4">
                      <h5>Moneda</h5>
                      <select class="form-control" id="busqueda_tipomoneda" form-key="id_tipo_moneda" style="text-align-last: center;">
                        <option value="">Todas</option>
                        @foreach ($tipo_monedas as $idx => $tm)
                        <option value="{{$tm->id_tipo_moneda}}">{{$tm->descripcion}}</option>
                        @endforeach
                      </select>
                    </div>
                    <div id="target_link" class="col-lg-3">
                      <h5>Nombre Progresivo</h5>
                      <input id="B_nombre_progresivo" type="text" class="form-control" placeholder="Nombre progresivo" form-key="nombre_progresivo">
                    </div>
                    <div id="target_individual" class="col-md-3">
                      <h5>Maquinas</h5>
                      <input id="B_maquinas" type="text" class="form-control" placeholder="Maquinas" form-key="maquinas">
                    </div>
                    <div class="col-lg-3">
                      <h5>Islas</h5>
                      <input id="B_islas" type="text" class="form-control" placeholder="Islas" form-key="islas">
                    </div>
                    <div class="col-lg-3">
                      <h5>Sectores</h5>
                      <input id="B_sectores" type="text" class="form-control" placeholder="Sectores" form-key="sectores">
                    </div>
                    <div class="col-lg-2 col-lg-offset-5">
                      <h5>&nbsp;</h5>
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
            <h4>PROGRESIVOS LINKEADOS</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultados" class="table table-fixed tablesorter">
              <thead>
                <tr>
                  <th class="col-xs-1" value="progresivo.id_casino" estado="">CASINO<i class="fa fa-sort"></i></th>
                  <th class="col-xs-1" value="tipo_moneda.descripcion" estado="">MONEDA<i class="fa fa-sort"></i></th>
                  <th class="col-xs-2" value="progresivo.nombre"    estado="">NOMBRE PROGRESIVO<i class="fa fa-sort"></i></th>
                  <th class="col-xs-2" value="maquinas"  estado="">MAQUINAS<i class="fa fa-sort"></i></th>
                  <th class="col-xs-2" value="islas"     estado="">ISLAS<i class="fa fa-sort"></i></td>
                  <th class="col-xs-2" value="sectores"             estado="">SECTORES<i class="fa fa-sort"></i></td>
                  <th class="col-xs-2">ACCIONES</th>
                </tr>
              </thead>
              <tbody id="cuerpoTabla" style="height: 350px;">
                <tr class="filaEjemplo" style='display: none;'>
                  <td class="col-xs-1 casino">CASINO</td>
                  <td class="col-xs-1 moneda">MONEDA</td>
                  <td class="col-xs-2">
                    <span class="nombre">NOMBREPROG</span>
                    <sup style="color: blue;" es_individual="0" hidden>LINK</sup>
                    <sup style="color: green;" es_individual="1" hidden>INDIV</sup>
                  </td>
                  <td class="col-xs-2 maquinas">MAQUINAS</td>
                  <td class="col-xs-2 islas">ISLA1/ISLA2/...</td>
                  <td class="col-xs-2 sectores">SECTOR1/SECTOR2/...</td>
                  <td class="col-xs-2 acciones">
                    <button class="btn btn-info grupal detalle">
                      <i class="fa fa-fw fa-search-plus"></i>
                    </button>
                    <span> </span>
                    <button class="btn btn-info grupal modificar">
                      <i class="fa fa-fw fa-pencil-alt"></i>
                    </button>
                    <span> </span>
                    <button class="btn btn-info grupal eliminar">
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
  </div>
</div>
<!-- Modal Progresivo -->
<div class="modal fade" id="modalProgresivo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-keyboard="false" aria-hidden="true">
  <div class="modal-dialog modal-lg" style="width: 80%;">
    <div class="modal-content">
      <div class="modal-header" style="background: #5cb85c;font-family: Roboto-Black;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title" style="color: #fff;">| NUEVO PROGRESIVO LINKEADO</h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body modal-Cuerpo">
          <div class="row">
            <div class="col-md-3 col-lg-3">
              <h5 class='row'>Nombre Progresivo</h5>
              <input id="nombre_progresivo" type="text" class="form-control" placeholder="Nombre Progresivo" autocomplete="off">
            </div>
            <div class="col-md-3 col-lg-3">
              <h5 class='row'>Porcentaje de recuperación</h5>
              <input id="porc_recup" type="text"  class="form-control" placeholder="0">
            </div>
            <div class="col-md-3 col-lg-3">
              <h5 class='row'>Casino</h5>
              <select class="form-control" id="modalProgresivo_casino">
                @foreach ($casinos as $idx => $c)
                <option value="{{$c->id_casino}}" {{$idx==0? 'selected' : ''}}>{{$c->nombre}}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3 col-lg-3">
              <h5 class='row'>Moneda</h5>
              <select class="form-control" id="modalProgresivo_tipomoneda">
                @foreach ($tipo_monedas as $idx => $tm)
                <option value="{{$tm->id_tipo_moneda}}" {{$idx==0? 'selected' : ''}}>{{$tm->descripcion}}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div id='modalProgresivo_cuerpo' class='row'>
            <hr>
            <div>
              <h3>Pozos</h3>
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
            <div>
              <div class="form-group form-inline">
                <label for="selectTipoProgresivo"><h3>Maquinas</h3></label>
                <select id="selectTipoProgresivo" class="form-control">
                  <option value="0">LINK</option>
                  <option value="1">INDIVIDUAL</option>
                </select>
              </div>
              <div class="row" id="enlazarMaquinaDiv">
                <div class="col-md-2">
                  <h5 style="text-align: right;">Enlazar maquina</h5>
                </div>
                <div class="col-md-2">
                  <div class="input-group lista-datos-group" style="display: inline-block;">
                    <input id="input-maquina" class="form-control " type="text" value="" autocomplete="off" style="float: revert;">
                  </div>
                </div>
                <div class="col-md-2">
                  <button id="btn-agregarMaquina" class="btn btn-success">
                    <i class="fa fa-fw fa-plus"></i>
                    <b>Enlazar</b>
                  </button>
                </div>
              </div>
              <div class="row" id="enlazarIslaDiv">
                <div class="col-md-2">
                  <h5 style="text-align: right;">Enlazar isla</h5>
                </div>
                <div class="col-md-2">
                  <div class="input-group lista-datos-group" style="display: inline-block;">
                    <input id="input-isla" class="form-control " type="text" value="" autocomplete="off" style="float: revert;">
                  </div>
                </div>
                <div class="col-md-2">
                  <button id="btn-agregarIsla" class="btn btn-success">
                    <i class="fa fa-fw fa-plus"></i>
                    <b>Enlazar</b>
                  </button>
                </div>
              </div>
              <div id="contenedorMaquinas" class="row" style="overflow-y: auto;overflow-x: hidden;height: 400px;"></div>
            </div>
          </div> <!-- /Fin panel minimizable -->

          <div class="modal-footer">
            <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">ACEPTAR</button>
            <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
          </div>
        </div> <!-- Fin modal-header -->
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalProgresivoIndividual" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-keyboard="false" aria-hidden="true">
  <div class="modal-dialog modal-lg" style="width: 80%;">
    <div class="modal-content">
      <div class="modal-header" style="background: #5cb85c;font-family: Roboto-Black;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title" style="color: #fff;">| NUEVOS PROGRESIVOS INDIVIDUALES</h3>
      </div>
      <div id="colapsado" class="collapse in">
        <div class="modal-body modal-Cuerpo">
          <div id='modalProgresivoIndividual_seccionSup'>
            <div class="row">
              <h3 class='col-md-3 col-lg-3'>Parametros base</h3>
              <div class='col-md-1 col-lg-1'></div>
              <h5 class='col-md-1 col-lg-1'>Casino</h5>
              <div class='col-md-3 col-lg-3 '>
                <select class="form-control" id="modalProgresivoIndividual_casino">
                  @foreach ($casinos as $idx => $c)
                  <option value="{{$c->id_casino}}" {{$idx==0? 'selected' : ''}}>{{$c->nombre}}</option>
                  @endforeach
                </select>
              </div>
              <h5 class='col-md-1 col-lg-1'>Moneda</h5>
              <div class='col-md-3 col-lg-3'>
                <select class="form-control" id="modalProgresivoIndividual_tipomoneda">
                  @foreach ($tipo_monedas as $idx => $tm)
                  <option value="{{$tm->id_tipo_moneda}}" {{$idx==0? 'selected' : ''}}>{{$tm->descripcion}}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="row no-gutters" id='modalProgresivoIndividual_seccionParametros'>
            <div class="col-md-3 col-lg-3">
              <h5>% Recuperación</h5>
              <input id="inputPorcRecupIndividual" class="editable form-control" type="text">
            </div>
            <div class="col-md-3 col-lg-3">
              <h5>Máximo</h5>
              <input id="inputMaximoIndividual" class="editable form-control" type="text">
            </div>
            <div class="col-md-2 col-lg-2">
              <h5>Base</h5>
              <input id="inputBaseIndividual" class="editable form-control" type="text">
            </div>
            <div class="col-md-2 col-lg-2">
              <h5>% Visible</h5>
              <input id="inputPorcVisibleIndividual" class="editable form-control" type="text">
            </div>
            <div class="col-md-2 col-lg-2">
              <h5>% Oculto</h5>
              <input id="inputPorcOcultoIndividual" class="editable form-control" type="text">
            </div>
          </div>
          <h3>Progresivos</h3>
          <div class="row">
            <div class="row">
              <div class="col-md-3">
                <h5>Agregar progresivo individual:</h5>
              </div>
              <div class="col-md-2">
                <div class="input-group lista-datos-group" style="display: inline-block;">
                  <input id="input-maquina-individual" class="form-control " type="text" value="" autocomplete="off" style="float: revert;">
                </div>
              </div>
              <div class="col-md-2">
                <button id="btn-agregarMaquinaIndividual" class="btn btn-success">
                  <i class="fa fa-fw fa-plus"></i>
                  <b>Agregar</b>
                </button>
              </div>
            </div>
            <div id="contenedorMaquinasIndividual"  style="overflow-y: auto;overflow-x: hidden;height: 400px;"></div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-successAceptar" id="btn-guardarIndividual" value="nuevo">ACEPTAR</button>
            <button type="button" class="btn btn-default" id="btn-cancelarIndividual" data-dismiss="modal" aria-label="Close">CANCELAR</button>
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
      <div class="modal-header" style="font-family: Roboto-Black; color: #EF5350">
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
  <div class="col-md-12">
    <div>
      <div class="panel-body">
        <table class="table table-condensed tablesorter tablaMaquinas">
          <thead>
            <tr>
              <th class="col-xs-2" value="maquina.nro_admin" estado="">#<i class="fa fa-sort"></i></th>
              <th class="col-xs-3" value="maquina.sector" estado="">Sector<i class="fa fa-sort"></i></th>
              <th class="col-xs-2" value="maquina.isla" estado="">Isla<i class="fa fa-sort"></i></th>
              <th class="col-xs-4" value="maquina.marca_juego" estado="">Marca juego<i class="fa fa-sort"></i></th>
              <th class="col-xs-1">ACCIONES</th>
            </tr>
          </thead>
          <tbody class="cuerpoTabla" style="overflow-y: auto;overflow-x: hidden;">
            <tr class="filaEjemplo">
              <td class="col-xs-2 grupal cuerpoTablaNroAdmin">999</td>
              <td class="col-xs-3 grupal cuerpoTablaSector">SECTOR999</td>
              <td class="col-xs-2 grupal cuerpoTablaIsla">999</td>
              <td class="col-xs-4 grupal cuerpoTablaMarcaJuego">SIN MARCA</td>
              <td class="col-xs-1 grupal cuerpoTablaAcciones">
                <button class="btn btn-info grupal unlink">
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
              <td class="col-xx-1 individual cuerpoTablaNroAdmin chico">999</td>
              <td class="col-xs-1 individual cuerpoTablaSector chico">SECTOR999</td>
              <td class="col-xs-1 individual cuerpoTablaIsla chico">999</td>
              <td class="col-xs-2 individual cuerpoTablaMarcaJuego chico">SIN MARCA</td>
              <td class="col-xs-1 individual cuerpoPorcRecup chico">99.99</td>
              <td class="col-xs-1 individual cuerpoMaximo chico">999999</td>
              <td class="col-xs-1 individual cuerpoBase chico">9999</td>
              <td class="col-xs-1 individual cuerpoPorcVisible chico">99.99</td>
              <td class="col-xs-1 individual cuerpoPorcOculto chico">99.99</td>
              <td class="col-xs-2 individual cuerpoTablaAcciones">
                <button class="btn btn-info individual editar">
                  <i class="fas fa-pencil-alt"></i>
                </button>
                <button class="btn btn-info individual eliminar">
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
<script src="/js/seccionProgresivos.js?6" charset="utf-8"></script>
<script src="/js/seccionProgresivosFilas.js" charset="utf-8"></script>
@endsection
