@extends('includes.dashboard')
@section('headerLogo')

@endsection
@section('estilos')
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="css/bootstrap-datetimepicker.css" rel="stylesheet"/>
<link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/css/paginacion.css">

@endsection

@section('contenidoVista')

<div class="row">
  <div class="col-md-3">
    <div class="row">
      <div class="col-md-12">
        <a href="" id="btn-sortear-fechas" dusk="btn-nuevo" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <center><img class="imgNuevo" src="/img/logos/informes_white.png"><center>
              <div class="backgroundNuevo"></div>
              <div class="row">
                <div class="col-xs-12">
                  <center>
                    <h5 class="txtLogo">+</h5>
                    <h4 class="txtNuevo">SORTEO FECHAS </h4>
                  </center>
                </div>
              </div>
          </div>
        </a>
      </div>
    </div>
  </div>

<div class="col-md-9">
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
          <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
        </div>
        <div id="collapseFiltros" class="panel-collapse collapse">
          <div class="panel-body">
            <div class="row">
              <div class="col-xs-3">
                <h5>Mes y Año de Sorteo</h5>
                <div class="form-group">
                  <div class='input-group date' id='dtpFecha' data-link-field="fecha_filtro" data-date-format="yyyy-MM" data-link-format="yyyy-MM">
                    <input type='text' class="form-control" id="mes_filtro" value="" placeholder="aaaa-mm"/>
                    <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                    <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>

              </div>
              <div class="col-xs-3">
                <h5>Casino</h5>
                <select class="form-control" name="" id="filtroCasinoImag" >
                  <option value="0" selected>- Todos los Casinos -</option>
                  @foreach ($casinos as $cas)
                    <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-xs-3">
                <h5>IDENTIFICACIÓN CD</h5>
                <input type="text" name="" value="" id="identificacion" class="form-control">
              </div>
              <br>
              <div class="col-md-3" style="padding-top:20px;">
                <button id="btn-buscar-imagenes" class="btn btn-infoBuscar" type="button" name="button" style="margin-top:10px">
                  <i class="fa fa-fw fa-search"></i> BUSCAR
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- TABLA -->
  <div class="row">
    <div class="col-xs-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4>DATOS DE CDS RECIBIDOS EN CADA SORTEO</h4>
        </div>
        <div class="panel-body">
          <div class="table-responsive">
            <table id="tablaSorteos" class="table tablesorter" >
              <thead>
                <tr align="center" >
                  <th class="col-xs-2 activa" value="img.created_at" style="font-size:14px; text-align:center !important;" estado="desc">MES <i class="fas fa-sort-down"></th>
                    <th class="col-xs-3" value="casino.nombre" style="font-size:14px; text-align:center !important;" estado="">CASINO  <i class="fas fa-sort"></th>
                  <th class="col-xs-3" style="font-size:14px; text-align:center !important;" >DÍAS SORTEADOS </th>
                  <th class="col-xs-2" style="font-size:14px; text-align:center !important;">ESTADO </th>
                  <th class="col-xs-2" style="font-size:14px; text-align:center !important;">DATOS</th>
                </tr>
              </thead>
              <tbody >

              </tbody>
            </table>
          </div>
          <div class="table-responsive" id="mostrarFila" style="display:none">
            <table  class="table">
              <tr id="moldeImag" class="filaClone" style="display:none">
                <td class="cloneMes"  style="text-align:center !important;"></td>
                <td class="cloneCasino"   style="text-align:center !important;"></td>
                <td class="cloneDias"  style="text-align:center !important;"></td>
                <td class="cloneEstado"   style="text-align:center !important;">


                </td>

                <td class="col-xs-3" style="text-align:center !important;">
                  <button type="button" class="btn btn-successAceptar cargarSorteo" data-toggle="tooltip" title="Cargar" value="">
                      <i class="fas fa-fw fa-upload"></i>
                  </button>
                  <button type="button" class="btn btn-warning verSorteo" value="">
                      <i class="fa fa-fw fa-search-plus"></i>
                  </button>
                  <button type="button" class="btn btn-warning modificarSorteo" value="">
                      <i class="fas fa-fw fa-pencil-alt"></i>
                  </button>

                </td>
              </tr>
            </table>
          </div>
          <legend></legend>
          <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<!-- Modal para cargar datos -->
<div class="modal fade" id="modalCargarDatos" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" style="width: 70%;">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#6dc7be;">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h3 class="modal-title">| CARGA DATOS</h3>
      </div>
      <div  class="collapse in">
        <div class="modal-body" >
          <div class="col-xs-12" style="padding-bottom:5px !important;margin-bottom:10px">
            <h6 style="font-size:16px; text-align:center !important;font-weight: bold;">DATOS DEL SORTEO</h6>
            <div class="col-xs-6">
              <h5>MES Y AÑO:</h5>
              <input type="text" name="" value="" class="form-control fechaSorteada">
              <br>
            </div>
            <div class="col-xs-6">
              <h5>CASINO:</h5>
              <input type="text" name="" value="" class="form-control casinoCarga">
              <br>
            </div>

          </div>

          <div class="col-xs-12" style="border-bottom: 1px solid #ccc;padding-bottom:15px !important;">
            <h6 style="font-size:16px;text-align:center !important;font-weight: bold;">DATOS DE LA UNIDAD DE ALMACENAMIENTO</h6>
            <div class="row">

            <div class="col-xs-4">
              <h5 style="font-size:14px !important">Cantidad de CDs recibidos:</h5>
              <div class="row">
                <div class="col-xs-6" style="margin-left:11px !important">
                  <input type="text" class="form-control cant_cds"  name="" value="" display="inline">
                </div>
                <div class="col-xs-2">
                  <button type="button" name="button" class="btn btn-infoBuscar okCDs" style="font-family:Roboto-Condensed;font-weight: bold;font-size: 15px;">OK</button>
                </div>
              </div>

            </div>

            <div class="col-xs-8" style="text-align:right !important" >
              <h5 style="text-align:center !important;font-size:14px !important">DATOS CDS:</h5>
            <table class="table table-sorter" id="tablaDatosCds" hidden="true" style="text-align:right !important">
              <tbody>
              </tbody>
            </table>
          </div>
          </div>
          <div class="col-xs-12" >
              <h6 style="color:red !important;float:left " hidden="true" id="errorCdId">Las identificaciones de los Cds deben ser diferentes</h6>
              <button type="button" name="button" class="btn btn-infoBuscar continuarCarga" style="font-family:Roboto-Condensed;font-weight: bold;font-size: 15px;float:right !important;" hidden="true">CONTINUAR</button>
          </div>
      </div>
      <br>
      <div class="row" style="border-bottom: 1px solid #ccc;" id="desplazarTMEsas" hidden="true">
        <br>
            <h6  style="font-size:16px; text-align:center !important;font-weight: bold;">DATOS DE LAS IMÁGENES</h6>
            <br>
            <ul class="nav nav-tabs nav-justified pestaniasFechas" id="pestaniasFechas" style=" width:80%;" hidden="true">
              <li id="b_1" ><a href="#fecha_1"  style="font-family:Roboto-condensed;font-size:20px; "> <h6 style="font-size:16px !important" class="nombreF1"></h6> </a></li>
              <li id="b_2"><a href="#fecha_2"  style="font-family:Roboto-condensed;font-size:20px;"><h6 style="font-size:16px !important" class="nombreF2"></h6></a></li>
              <li id="b_3"><a href="#fecha_3"  style="font-family:Roboto-condensed;font-size:20px;"><h6 style="font-size:16px !important" class="nombreF3"></h6></a></li>
           </ul>
           <div class="col-xs-12 tab_content" id="fecha_1" hidden="true">

            <table class="table tablesorter" id="tablaMesasSorteadas" hidden="true">
              <thead>
                <th class="col-xs-2" style="text-align:center !important">MESA</th>
                <th class="col-xs-2" style="text-align:center !important">CD</th>
                <th class="col-xs-2" style="text-align:center !important">DROP</th>
                <th class="col-xs-2" style="text-align:center !important">MIN.VIDEO</th>
                <th class="col-xs-2" style="text-align:center !important">COINCIDE CON LO IMPORTADO</th>
                <th class="col-xs-2" style="text-align:center !important">DIFERENCIAS</th>
              </thead>
              <tbody>

              </tbody>
            </table>

          </div>
          <div class="col-xs-12 tab_content" id="fecha_2" hidden="true">

             <table class="table tablesorter" id="tablaMesasSorteadas2" hidden="true">
               <thead>
                 <th class="col-xs-2" style="text-align:center !important">MESA</th>
                 <th class="col-xs-2" style="text-align:center !important">CD</th>
                 <th class="col-xs-2" style="text-align:center !important">DROP</th>
                 <th class="col-xs-2" style="text-align:center !important">MIN.VIDEO</th>
                 <th class="col-xs-2" style="text-align:center !important">COINCIDE CON LO IMPORTADO</th>
                 <th class="col-xs-2" style="text-align:center !important">DIFERENCIAS</th>
               </thead>
               <tbody>

               </tbody>
             </table>

           </div>
          <div class="col-xs-12 tab_content" id="fecha_3" hidden="true">

              <table class="table tablesorter" id="tablaMesasSorteadas3" hidden="true">
                <thead>
                  <th class="col-xs-2" style="text-align:center !important">MESA</th>
                  <th class="col-xs-2" style="text-align:center !important">CD</th>
                  <th class="col-xs-2" style="text-align:center !important">DROP</th>
                  <th class="col-xs-2" style="text-align:center !important">MIN.VIDEO</th>
                  <th class="col-xs-2" style="text-align:center !important">COINCIDE CON LO IMPORTADO</th>
                  <th class="col-xs-2" style="text-align:center !important">DIFERENCIAS</th>
                </thead>
                <tbody>

                </tbody>
              </table>

            </div>
          </div>
          <br>
          <div class="row verObs" hidden="true">
            <h6 style="font-size:16px;margin-left:15px !important">OBSERVACIONES: </h6>
            <textarea name="name" id="obsSorteo" style="width:70% !important;height:auto !important; margin-left:15px" class="estilotextarea4"></textarea>
          </div>
          <br>
        </div>
        <br>
        <div class="modal-footer">
          <br>
          <span class="help-block" style="color: #0D47A1 !important;margin-top:5px !important; font-size:12px !important;padding-left:5px !important; float:left;">
            <i>*En caso de no cargar todos los datos, pueden modificarse/agregarse una vez guardados.</i></span>
          <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo" hidden="true">GUARDAR</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL VER DETALLES DEL SORTEO CARGADO -->
<div class="modal fade" id="modalVerDatos" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" style="width: 80% !important;">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#0D47A1;">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h3 class="modal-title">| DETALLES</h3>
      </div>
      <div  id="colapsadoNuevo" class="collapse in">
        <div class="modal-body modalCuerpo" >
          <div class="row">
            <div class="col-xs-12">
              <h6 class="list-group-item"  style="font-size:16px !important; text-align:center !important; background-color:#aaa; color:white;">DATOS DEL SORTEO</h6>
              <br>
            </div>
            <br>
            <div class="row" >
              <div class="col-xs-6" >
                <h6 class="list-group-item"  style="font-size:16px !important; text-align:center !important; background-color:#aaa; color:white;">MES Y AÑO</h6>
                <h6 class="list-group-item" style="text-align:center !important; margin-top:0px !important; font-size:16px !important" id="fechaSorteadaVer" readonly="true"></h6>
              </div>
              <div class="col-xs-6" >
                <h6 class="list-group-item"  style="font-size:16px !important; text-align:center !important; background-color:#aaa; color:white;">CASINO</h6>
                <h6 class="list-group-item" style="text-align:center !important; margin-top:0px !important; font-size:16px !important" id="casinoVer" readonly="true"></h6>
              </div>

          </div>
        </div>
        <br>
        <div class="row">
        <div class="col-xs-12">
          <h6 class="list-group-item"  style="font-size:16px !important; text-align:center !important; background-color:#aaa; color:white;">DATOS DE LAS IMÁGENES</h6>
          <br>
        </div>
            <ul class="nav nav-tabs nav-justified pestaniasFechasVer" id="pestaniasFechasVer" style=" width:80%;" hidden="true">
              <li id="b_1_ver" ><a href="#fecha_1ver"  style="font-family:Roboto-condensed;font-size:20px; "> <h6 style="font-size:16px !important" class="nombreF1"></h6> </a></li>
              <li id="b_2_ver"><a href="#fecha_2ver"  style="font-family:Roboto-condensed;font-size:20px;"><h6 style="font-size:16px !important" class="nombreF2"></h6></a></li>
              <li id="b_3_ver"><a href="#fecha_3ver"  style="font-family:Roboto-condensed;font-size:20px;"><h6 style="font-size:16px !important" class="nombreF3"></h6></a></li>
           </ul>
           <div class="col-xs-12 tab_content" id="fecha_1ver" hidden="true">
            <table class="table tablesorter" id="tablaMesasSorteadasver" hidden="true">
              <thead>
                <th class="col-xs-2" style="text-align:center !important"><h6 style="font-size:14px !important;color:#000;">MESA</h6></th>
                <th class="col-xs-2" style="text-align:center !important"><h6 style="font-size:14px !important;color:#000;">CD</h6></th>
                <th class="col-xs-3" style="text-align:center !important"><h6 style="font-size:14px !important;color:#000;">DROP</h6></th>
                <th class="col-xs-2" style="text-align:center !important"><h6 style="font-size:14px !important;color:#000;">MIN.VIDEO</h6></th>
                <th class="col-xs-3" style="text-align:center !important"><h6 style="font-size:14px !important;color:#000;">DIFERENCIAS</h6></th>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
          <div class="col-xs-12 tab_content" id="fecha_2ver" hidden="true">
             <table class="table tablesorter" id="tablaMesasSorteadas2ver" hidden="true">
               <thead>
                 <th class="col-xs-2" style="text-align:center !important"><h6 style="font-size:14px !important;color:#000;">MESA</h6></th>
                 <th class="col-xs-2" style="text-align:center !important"><h6 style="font-size:14px !important;color:#000;">CD</h6></th>
                 <th class="col-xs-3" style="text-align:center !important"><h6 style="font-size:14px !important;color:#000;">DROP</h6></th>
                 <th class="col-xs-2" style="text-align:center !important"><h6 style="font-size:14px !important;color:#000;">MIN.VIDEO</h6></th>
                 <th class="col-xs-3" style="text-align:center !important"><h6 style="font-size:14px !important;color:#000;">DIFERENCIAS</h6></th>
               </thead>
               <tbody>
               </tbody>
             </table>
           </div>
          <div class="col-xs-12 tab_content" id="fecha_3ver" hidden="true">
              <table class="table tablesorter" id="tablaMesasSorteadas3ver" hidden="true">
                <thead>
                  <th class="col-xs-2" style="text-align:center !important"><h6 style="font-size:14px !important;color:#000;">MESA</h6></th>
                  <th class="col-xs-2" style="text-align:center !important"><h6 style="font-size:14px !important;color:#000;">CD</h6></th>
                  <th class="col-xs-3" style="text-align:center !important"><h6 style="font-size:14px !important;color:#000;">DROP</h6></th>
                  <th class="col-xs-2" style="text-align:center !important"><h6 style="font-size:14px !important;color:#000;">MIN.VIDEO</h6></th>
                  <th class="col-xs-3" style="text-align:center !important"><h6 style="font-size:14px !important;color:#000;">DIFERENCIAS</h6></th>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div>
          </div>
          <br>
          <div class="row">
            <h6 style="font-size:16px;margin-left:15px !important">OBSERVACIONES: </h6>
            <textarea name="name" id="obsSorteoVer" style="width:70% !important;height:auto !important; margin-left:15px" readonly="true" class="estilotextarea4"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal para modificar datos -->
<div class="modal fade" id="modalModificarDatos" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" style="width: 80% !important;">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#FFA726;">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h3 class="modal-title">| MODIFICAR DATOS</h3>
      </div>
      <div  id="colapsadoNuevo" class="collapse in">
        <div class="modal-body modalCuerpo" >
          <div class="row" style="border-bottom: 1px solid #ccc;padding-bottom:15px !important;">
            <h6 style="font-size:16px;text-align:center !important">DATOS DEL SORTEO</h6>
            <div class="col-xs-6">
              <h5>MES Y AÑO:</h5>
              <input type="text" name="" value="" class="form-control fechaSorteadaMod" readonly="true">
            </div>
            <div class="col-xs-6">
              <h5>CASINO:</h5>
              <input type="text" name="" value="" class="form-control casinoMod" readonly="true">
            </div>
          </div>
          <br>
          <div class="row" style="border-bottom: 1px solid #ccc;padding-bottom:15px !important;">
            <h6 style="font-size:16px;text-align:center !important">DATOS DE LA UNIDAD DE ALMACENAMIENTO</h6>
            <br>
            <div class="row">
            <div class="col-xs-4">
              <div class="row">
                <div class="col-xs-6" style="text-align:center !important">
                  <button type="button" name="button" class="btn btn-infoBuscar agregarCd" data-mod="1" style="text-align:center !important;font-family:Roboto-Condensed;font-weight: bold;font-size: 15px;margin-left:15px;margin-top:15px">AGREGAR CD</button>
                </div>
              </div>
            </div>
            <div class="col-xs-8" style="text-align:right !important" >
              <h5 style="text-align:center !important">DATOS CDS:</h5>
            <table class="table table-sorter" id="tablaDatosCdsMod"  style="text-align:right !important">
              <tbody>
              </tbody>
            </table>
          </div>
        </div>

          </div>
          <br>
            <h6 style="font-size:16px;text-align:center !important">DATOS DE LAS IMÁGENES</h6>
            <br>
            <ul class="nav nav-tabs nav-justified pestaniasFechasMod" id="pestaniasFechasMod" style=" width:80%;" hidden="true">
              <li id="b_1_mod" ><a href="#fecha_1mod"  style="font-family:Roboto-condensed;font-size:20px; "> <h6 style="font-size:16px !important" class="nombreF1"></h6> </a></li>
              <li id="b_2_mod"><a href="#fecha_2mod"  style="font-family:Roboto-condensed;font-size:20px;"><h6 style="font-size:16px !important" class="nombreF2"></h6></a></li>
              <li id="b_3_mod"><a href="#fecha_3mod"  style="font-family:Roboto-condensed;font-size:20px;"><h6 style="font-size:16px !important" class="nombreF3"></h6></a></li>
           </ul>
           <div class="col-xs-12 tab_content" id="fecha_1mod" hidden="true">
            <table class="table tablesorter" id="tablaMesasSorteadasMod" hidden="true">
              <thead>
                <th class="col-xs-2" style="text-align:center !important">MESA</th>
                <th class="col-xs-2" style="text-align:center !important">CD</th>
                <th class="col-xs-2" style="text-align:center !important">DROP</th>
                <th class="col-xs-2" style="text-align:center !important">MIN.VIDEO</th>
                <th class="col-xs-2" style="text-align:center !important">COINCIDE CON LO IMPORTADO</th>
                <th class="col-xs-2" style="text-align:center !important">DIFERENCIAS</th>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
          <div class="col-xs-12 tab_content" id="fecha_2mod" hidden="true">

             <table class="table tablesorter" id="tablaMesasSorteadas2Mod" hidden="true">
               <thead>
                 <th class="col-xs-2" style="text-align:center !important">MESA</th>
                 <th class="col-xs-2" style="text-align:center !important">CD</th>
                 <th class="col-xs-2" style="text-align:center !important">DROP</th>
                 <th class="col-xs-2" style="text-align:center !important">MIN.VIDEO</th>
                 <th class="col-xs-2" style="text-align:center !important">COINCIDE CON LO IMPORTADO</th>
                 <th class="col-xs-2" style="text-align:center !important">DIFERENCIAS</th>
               </thead>
               <tbody>
               </tbody>
             </table>
           </div>
          <div class="col-xs-12 tab_content" id="fecha_3mod" hidden="true">
              <table class="table tablesorter" id="tablaMesasSorteadas3Mod" hidden="true">
                <thead>
                  <th class="col-xs-2" style="text-align:center !important">MESA</th>
                  <th class="col-xs-2" style="text-align:center !important">CD</th>
                  <th class="col-xs-2" style="text-align:center !important">DROP</th>
                  <th class="col-xs-2" style="text-align:center !important">MIN.VIDEO</th>
                  <th class="col-xs-2" style="text-align:center !important">COINCIDE CON LO IMPORTADO</th>
                  <th class="col-xs-2" style="text-align:center !important">DIFERENCIAS</th>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div>
          <br>
          <div class="row">
            <h6 style="font-size:16px;margin-left:15px !important">OBSERVACIONES: </h6>
            <textarea name="name" id="obsSorteoMod" style="width:70% !important;height:auto !important; margin-left:15px" readonly="true" class="estilotextarea4"></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-warningModificar" id="btn-guardar-modificar" data-mod="1" value="nuevo" hidden="true">GUARDAR</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          <input type="text" id="arrayCds" name="" value="" hidden="true">
        </div>
        <div id="mensajeDatosCds" hidden>
          <br>
          <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
          <br>
          <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">DEBEN CARGARSE DATOS DE LOS CDS RECIBIDOS.</span>
        </div> <!-- mensaje -->
      </div>
    </div>
  </div>
</div>

<!-- MODAL QUE GENERA EL SORTEO -->
<div class="modal fade" id="modalSorteo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" style="width: 70%;">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#0D47A1;">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h3 class="modal-title">| GENERANDO SORTEO</h3>
      </div>
      <div  id="colapsadoNuevo" class="collapse in">
        <div class="modal-body modalCuerpo" >
          <div class="loading" style="animation-delay:3s;text-align:center !important" >
            <img src="/img/ajax-loader(1).gif" alt="loading" />
            <br><i>Un momento, por favor...</i>
          </div>
          <div class="row detallesFechas" hidden="true">
            <div class="alert alert-success" role="alert" id="alertaCanon" >
              <h6 class="alert-heading" style="text-align:center !important">SORTEO GENERADO! </h6>
            </div>
            <br>
            <div class="row " id="datosPorCasinos" style="display:none;">
              <h6 class="casinoNombre" style="background-color:#ccc !important; padding-top:5px !important;padding-bottom:5px !important;"></h6>
              <h4 style="font-size:20px !important!;font-family:Roboto-Regular !important;" class="mesAnio"> </h4>
              <h4 style="font-size:20px !important!;font-family:Roboto-Regular !important;" class="fechasVer"> </h4>
              <h4 style="font-size:20px !important!;font-family:Roboto-Regular !important;" class="mesasVer"> </h4>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')

  <!-- JavaScript personalizado -->
  <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
  <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

  <script src="/js/fileinput.min.js" type="text/javascript"></script>
  <script src="/js/locales/es.js" type="text/javascript"></script>
  <script src="/themes/explorer/theme.js" type="text/javascript"></script>
  <script src="/js/jquery-ui.js" type="text/javascript"></script>

  <!-- JS paginacion -->
  <script src="/js/paginacion.js" charset="utf-8"></script>

  <script src="js/SolicitudImagenes/seccionImagenes.js" type="text/javascript" charset="utf-8"></script>

@endsection
