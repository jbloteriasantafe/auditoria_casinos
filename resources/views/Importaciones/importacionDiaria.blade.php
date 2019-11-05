@extends('includes.dashboard')

@section('headerLogo')

@endsection
@section('estilos')
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="css/bootstrap-datetimepicker.css" rel="stylesheet"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/js/jquery-ui-1.12.1.custom/jquery-ui.css">
<link rel="stylesheet" href="/css/paginacion.css">

@endsection
@section('contenidoVista')

<div class="col-lg-12 tab_content" id="pest_diaria" hidden="true">

  <div class="row">
    <div class="col-xl-3">
          <div class="row">
            <div class="col-md-12">
              <a href="" id="btn-importar" dusk="btn-nuevo" style="text-decoration: none;">
                <div class="panel panel-default panelBotonNuevo">
                  <center><img class="imgNuevo" src="/img/logos/informes_white.png"><center>
                    <div class="backgroundNuevo"></div>
                      <div class="row">
                        <div class="col-xs-12">
                          <center>
                              <h5 class="txtLogo">+</h5>
                              <h4 class="txtNuevo">IMPORTAR INFORME DIARIO</h4>
                          </center>
                        </div>
                        </div>
                    </div>
                  </a>
              </div>
            </div>

        </div>

    <div class="col-xl-9">
          <!-- FILTROS -->
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
                              <h5>Fecha</h5>
                              <div class="form-group">
                                <div class='input-group date' id='dtpFecha' data-link-field="fecha_filtro" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                                  <input type='text' class="form-control" id="B_fecha_filtro" placeholder="aaaa-mm-dd" value=" "/>
                                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                </div>
                              </div>
                            </div>

                            <div class="col-xs-3">
                              <h5>Casino</h5>
                              <select class="form-control" name="" id="filtroCas" >
                                <option value="0" selected>- Todos los Casinos -</option>
                                @foreach ($casinos as $cas)
                                <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                                @endforeach
                              </select>
                            </div>
                            <div class="col-xs-3">
                              <h5>Moneda</h5>
                              <select class="form-control" name="" id="filtroMon" >
                                <option value="0" selected>- Todas las Monedas -</option>
                                @foreach ($moneda as $mon)
                                <option value="{{$mon->id_moneda}}">{{$mon->descripcion}}</option>
                                @endforeach
                              </select>
                            </div>
                            <div class="col-xs-3" >
                              <button id="buscar-importacionesDiarias" style="margin-top:30px" class="btn btn-infoBuscar" type="button" name="button">
                                <i class="fa fa-fw fa-search"></i> BUSCAR
                              </button>
                          </div>
                        </div>
                        </div> <!-- panel-body -->
                    </div> <!-- collapse -->
                  </div> <!-- .panel-default -->
                </div> <!-- .col-md-12 -->

          </div> <!-- .row / FILTROS -->

          <!-- TABLA -->
          <div class="row">
              <div class="col-md-12">
                <div class="panel panel-default">
                  <div class="panel-heading">
                    <h4>Importaciones Diarias</h4>
                  </div>
                  <div class="panel-body">
                    <table id="tablaResultadosDiarios" class="table table-fixed tablesorter ">
                      <thead>
                        <tr align="center" >
                          <th class="col-xs-2 activa" estado="desc" value="fecha" style="font-size:14px; text-align:center !important;">FECHA<i class="fas fa-sort-down"></i></th>
                          <th class="col-xs-3" estado="desc" value="casino.nombre" style="font-size:14px; text-align:center !important;">CASINO<i class="fas fa-sort"></i></th>
                          <th class="col-xs-2" estado="desc" value="moneda.descripcion" style="font-size:14px; text-align:center !important;">MONEDA<i class="fas fa-sort"></i></th>
                          <th class="col-xs-2" style="font-size:14px; text-align:center !important;">ESTADO</th>
                          <th class="col-xs-3"  style="font-size:14px; text-align:center !important;">ACCIÓN</th>
                        </tr>
                      </thead>
                      <tbody  id='cuerpoTablaImpD' style="height: 380px;">
                        @foreach($diarias as $d)
                        <tr id="{{$d->id_importacion_diaria_mesas}}" >

                        <td class="col-xs-2" style="text-align:center !important;">{{$d->fecha}}</td>
                        <td class="col-xs-3" style="text-align:center !important;">{{$d->casino->nombre}}</td>
                        <td class="col-xs-2"  style="text-align:center !important;">{{$d->moneda->descripcion}}</td>
                        @if($d->diferencias == 0)
                          <td class="col-xs-2"  style="text-align:center !important;color: #4CAF50"><i class="fas fa-check-circle"></i></td>
                        @else
                          <td class="col-xs-2"  style="text-align:center !important;color:#D32F2F"><i class="fa fa-fw fa-times"></i></td>
                        @endif

                        <td class="col-xs-3" style="text-align:center !important;">
                          @if($d->validado==1)
                          <button type="button" class="btn btn-info infoImpD" style="align:right !important;" value="{{$d->id_importacion_diaria_mesas}}">
                                  <i class="fa fa-fw fa-search-plus" ></i>
                          </button>
                          @else
                          <button type="button" class="btn btn-info obsImpD" style="align:right !important;" value="{{$d->id_importacion_diaria_mesas}}">
                                  <i class="fa fa-fw fa-check"></i>
                          </button>
                          <button type="button" class="btn btn-success eliminarDia" value="{{$d->id_importacion_diaria_mesas}}" >
                                  <i class="fa fa-fw fa-trash"></i>
                          </button>
                          @endif

                        </td>

                      </tr>
                        @endforeach
                      </tbody>
                    </table>
                    <table>
                      <tbody>
                        <tr id="moldeFilaImpD" class="filaClone" style="display:none">
                          <td class="col-xs-2 d_fecha" style="text-align:center !important;"></td>
                          <td class="col-xs-3 d_casino" style="text-align:center !important;"></td>
                          <td class="col-xs-2 d_moneda" style="text-align:center !important;"></td>
                          <td class="col-xs-2 d_dif" style="text-align:center !important;"></td>

                          <td class="col-xs-3 d_accion" style="text-align:center !important;">
                            <button type="button" class="btn btn-info infoImpD" value="" >
                                    <i class="fas fa-fw fa-search-plus"></i>
                            </button>
                            <button type="button" class="btn btn-info obsImpD" value="">
                                    <i class="fa fa-fw fa-check"></i>
                            </button>
                            <button type="button" class="btn btn-success eliminarDia" value="">
                                    <i class="fa fa-fw fa-trash"></i>
                            </button>

                          </td>
                        </tr>
                      </tbody>
                    </table>
                    <div id="herramientasPaginacion" class="row zonaPaginacion"></div>

                    </div>
                  </div>
                </div>
          </div>
    </div> <!-- .row / TABLA -->




</div> <!-- col-xl-3 | COLUMNA DERECHA - BOTONES -->

</div>

<div class="col-lg-12 tab_content" id="pest_mensual" hidden="true">

  <div class="row">
    <div class="col-xl-3">
      <div class="row">
        <div class="col-md-12">
          <a href="" id="btn-importar-mes" dusk="btn-nuevo" style="text-decoration: none;">
            <div class="panel panel-default panelBotonNuevo">
              <center><img class="imgNuevo" src="/img/logos/informes_white.png"><center>
                <div class="backgroundNuevo"></div>
                <div class="row">
                  <div class="col-xs-12">
                    <center>
                      <h5 class="txtLogo">+</h5>
                      <h4 class="txtNuevo">IMPORTAR INFORME MENSUAL</h4>
                    </center>
                  </div>
                </div>
              </div>
            </a>
          </div>
        </div>
      </div>

    <div class="col-xl-9">
          <!-- FILTROS -->
        <div class="row">
          <div class="col-md-12">
              <div class="panel panel-default">
                  <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros1" style="cursor: pointer">
                      <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
                  </div>
                  <div id="collapseFiltros1" class="panel-collapse collapse">
                    <div class="panel-body">

                        <div class="row">
                            <div class="col-xs-3">
                              <h5>Fecha</h5>
                              <div class="form-group">
                                <div class='input-group date' id='dtpFechaFiltro' data-link-field="fecha_filtro" data-date-format="yyyy-MM" data-link-format="yyyy-MM">
                                  <input type='text' class="form-control" id="filtroFecha" value="" placeholder="aaaa-mm"/>
                                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                </div>
                              </div>
                            </div>

                            <div class="col-xs-3">
                              <h5>Casino</h5>
                              <select class="form-control" name="" id="filtroCasino" >
                                <option value="0" selected>- Todos los Casinos -</option>
                                @foreach ($casinos as $cas)
                                <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                                @endforeach
                              </select>
                            </div>

                            <div class="col-xs-3">
                              <h5>Moneda</h5>
                              <select class="form-control" name="" id="filtroMoneda" >
                                <option value="0" selected>- Todas las Monedas -</option>
                                @foreach ($moneda as $mon)
                                <option value="{{$mon->id_moneda}}">{{$mon->descripcion}}</option>
                                @endforeach
                              </select>
                            </div>

                            <div class="col-xs-3" >
                              <button id="buscar-impMensuales" style="margin-top:30px" class="btn btn-infoBuscar" type="button" name="button">
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
              <div class="col-md-12">
                <div class="panel panel-default">
                  <div class="panel-heading">
                    <h4 >Importaciones Mensuales</h4>
                  </div>
                  <div class="panel-body">
                    <table id="tablaResultadosMes" class="table table-fixed tablesorter">
                      <thead>
                        <tr align="center" >
                          <th class="col-xs-3 activa" estado="desc" value="fecha_mes" style="font-size:14px;text-align:center !important;">FECHA<i class="fas fa-sort-down"></i></th>
                          <th class="col-xs-2" estado="desc" value="casino.nombre" style="font-size:14px;text-align:center !important;">CASINO<i class="fas fa-sort"></i></th>
                          <th class="col-xs-2" estado="desc" value="moneda.descripcion" style="font-size:14px; text-align:center !important;">MONEDA<i class="fas fa-sort"></i></th>
                          <th class="col-xs-3" style="font-size:14px; text-align:center !important;">DIFERENCIAS</th>
                          <th class="col-xs-2"  style="font-size:14px;text-align:center !important;">ACCIÓN</th>
                        </tr>
                      </thead>
                      <tbody  id='cuerpoTablaImpM' >

                      </tbody>
                    </table>
                    <table>
                        <tr id="moldeFilaImpM" class="filaClone" style="display:none">
                          <td class="col-xs-3 m_fecha" style="text-align:center !important"></td>
                          <td class="col-xs-2 m_casino" style="text-align:center !important"></td>
                          <td class="col-xs-2 m_moneda" style="text-align:center !important;"></td>
                          <td class="col-xs-3 m_dif" style="text-align:center !important;"></td>

                          <td class="col-xs-2 m_accion" style="text-align:center !important;">
                            <button type="button" class="btn btn-info infoImpM" value="">
                                    <i class="fas fa-fw fa-search-plus"></i>
                            </button>
                            <button type="button" class="btn btn-info obsImpM" value="">
                                    <i class="fa fa-fw fa-check"></i>
                            </button>
                            <button type="button" class="btn btn-success eliminarMes" value="" >
                                    <i class="fa fa-fw fa-trash"></i>
                            </button>

                          </td>
                        </tr>
                    </table>
                    <div id="herramientasPaginacionMensual" class="row zonaPaginacion"></div>

                    </div>
                  </div>
                </div>
          </div>
    </div>
  </div>
</div>

<!-- ver estilos importacion, archivo de css en documentos -->
<!-- IMPORTACIONES DIARIAS MODALES -->

<!-- Modal Importacion -->
<div class="modal fade" id="modalImportacionDiaria" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
      <div class="modal-dialog" style="width: 70%">
         <div class="modal-content">
           <div class="modal-header"  style="font-family: Roboto-Black; background-color: #6dc7be;">
             <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
             <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
             <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
             <h3 class="modal-title">| IMPORTAR INFORME DIARIO DE MESAS</h3>
            </div>

            <div  id="colapsado" class="collapse in">

            <div class="modal-body modalCuerpo">
                    <!-- Estilos del mansaje de información -->
                    <style media="screen">
                      #mensajeInvalido i {
                        color: #FF5252;
                        position: relative;
                        top: -3px;
                        left: -10px;
                        transform: scale(2);
                      }
                      #mensajeInvalido h6 {
                        margin-left: 6px !important;
                        color: #FF1744;
                        display:inline;
                        font-size: 20px;
                        font-weight:bold !important; font-family:Roboto-Condensed !important
                      }
                      #mensajeInvalido p {
                        /*color: black;*/
                        display:inline-block;
                        font-size: 16px;
                        /*font-weight:bold !important; */
                        font-family:Roboto-Regular !important
                      }

                      #mensajeInformacion h6 {
                          margin-left: 10px;
                          display:inline;
                          font-size: 20px;
                          font-weight:bold !important; font-family:Roboto-Condensed !important;
                      }

                      #mensajeInformacion i {
                          position: relative;
                          top: -3px;
                          /*transform: scale(0.7);*/
                          color: #6DC7BE;
                      }

                      #mensajeInformacion i.corrido {
                          margin-left: 10px;
                      }

                      #iconoMoneda {
                        transform: scale(1.2);
                      }
                  </style>

              <form id="frmImportacion" name="frmMaquina" class="form-horizontal" novalidate="">
                <div class="col-xs-3 rowFecha">
                  <h5>FECHA*</h5>
                  <div class="form-group">
                    <div class='input-group date' id='dtpFechaImp' data-link-field="fecha_importacion" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                      <input type='text' class="form-control" placeholder="aaaa-mm-dd" id="B_fecha_imp" value=""/>
                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                    <input class="form-control" type="hidden" id="fecha_importacion" value=""/>
                  </div>
                </div>
                <div class="col-xs-3 rowCasino">
                  <h5>CASINO*</h5>
                  <select class="form-control" id="casinoSel">
                    <option value="0" selected>- Seleccione un Casino -</option>
                    @foreach ($casinos as $cas)
                    <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                    @endforeach

                  </select>
                </div>
                <div class="col-xs-3 rowMoneda">
                  <h5>MONEDA*</h5>
                  <select class="form-control" id="monedaSel">
                    <option value="0" selected>- Seleccione Moneda -</option>
                    <option value="1">PESOS</option>
                    <option value="2">DÓLARES</option>

                  </select>
                </div>
                <div class="col-xs-3 rowCotizacionDiaria">
                  <h5>COTIZACIÓN DOLAR</h5>
                  <input id="cotizacion_diaria" type="text" class="form-control" value="">
                </div>
              </form>

              <div id="rowArchivo" class="row" style="">
                      <div class="col-xs-12">
                        <div class="zona-file">
                          <h5>ARCHIVO</h5>
                            <input id="archivo" data-borrado="false" type="file" name="" >
                            <br> <span id="alertaArchivo" class="alertaSpan"></span>
                        </div>
                      </div>
              </div>



              <div id="mensajeError" class="row" style="margin-bottom:20px !important; margin-top: 20px !important;">
                      <div class="col-md-12">
                          <h6>SE PRODUJO UN ERROR DE CONEXIÓN</h6>
                          <button id="btn-reintentarContador" class="btn btn-info" type="button" name="button">REINTENTAR IMPORTACIÓN</button>
                      </div>
                  </div>



              <div id="mensajeInvalido" class="row" style="margin-bottom:20px !important; margin-top: 20px !important;">
                        <div class="col-xs-12" align="center">
                            <i class="fa fa-fw fa-exclamation-triangle"></i>
                            <h6> ARCHIVO INCORRECTO</h6>
                        </div>
                        <br>
                        <br>
                        <div class="col-xs-12" align="center">
                            <p>Solo se aceptan archivos con extensión .csv o .txt</p>
                        </div>
                  </div>

              <div id="mensajeInformacion" class="row" style="margin-bottom:20px !important; margin-top: 50px !important;">
                      <div class="col-xs-12" align="center">
                          <i class="fa fa-fw fa-star"></i>
                          <h6 id="informacionCasino"> CASINO ROSARIO</h6>
                          <i class="fa fa-fw fa-calendar corrido"></i>
                          <h6 id="informacionFecha">10 OCTUBRE 2017</h6>
                          <i id="iconoMoneda" class="fa fa-fw fa-usd corrido"></i>
                          <h6 id="informacionMoneda"> DOLAR</h6>

                      </div>
                  </div>

                  <div class="loading" id="iconoCarga" style="text-align: center" hidden="true">
                    <img src="/img/ajax-loader(1).gif" alt="loading" />
                    <br>Un momento, por favor...
                  </div>


              <!-- <div id="iconoCarga" class="sk-folding-cube">
                  <div class="windows8">
                	<div class="wBall" id="wBall_1">
                		<div class="wInnerBall"></div>
                	</div>
                	<div class="wBall" id="wBall_2">
                		<div class="wInnerBall"></div>
                	</div>
                	<div class="wBall" id="wBall_3">
                		<div class="wInnerBall"></div>
                	</div>
                	<div class="wBall" id="wBall_4">
                		<div class="wInnerBall"></div>
                	</div>
                	<div class="wBall" id="wBall_5">
                		<div class="wInnerBall"></div>
                	</div>
                </div>
              </div> -->

            </div>
            <div class="modal-footer">
              <span style="font-family:sans-serif;float:left !important;font-size:12px;color:#0D47A1"> * Campos Obligatorios</span>
              <button type="button" class="btn btn-successAceptar" id="btn-guardarDiario" value="nuevo"> SUBIR</button>
              <button type="button" class="btn btn-default" data-dismiss="modal"> CANCELAR</button>
            </div>
            <div id="mensajeErrorJuegos" hidden>
                <br>
                <div class="col-xs-12" style="display: inline-block;">
                    <i class="fa fa-fw fa-exclamation-triangle" style="color:#C62828;display: inline-block;" ></i>
                    <h6 style="display: inline-block;"> ARCHIVO INCORRECTO</h6>
                </div>
                <br>
                <p id="span" style="font-family:'Roboto-Regular'; font-size:16px; color:#555;"></p>
            </div> <!-- mensaje -->
          </div>
        </div>
      </div>
</div>

<!-- Modal para agregar observación -->
<div class="modal fade" id="modalVerImportacion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" style="width:60%">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| VALIDAR IMPORTACIÓN DIARIA</h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row">
            <div class="col-xs-3">
              <h5>FECHA</h5>
              <input id="fechaImpD" class="form-control" type="text" value=""  size="100" autocomplete="off" readonly="true">

            </div>
            <div class="col-xs-3">
              <h5>CASINO</h5>
              <input id="casinoImpD" class="form-control" type="text" value=""  size="100" autocomplete="off" readonly="true">

            </div>
            <div class="col-xs-3">
              <h5>MONEDA</h5>
              <input id="monedaImpD" class="form-control" type="text" value=""  size="100" autocomplete="off" readonly="true">
            </div>
            <div class="col-xs-3">
              <h5>TIPO DE MESA</h5>
              <select class="form-control" name="" id="selectMesa">
                <option value="1" selected>- RULETA -</option>
                <option value="2">- CARTAS -</option>
                <option value="3">- DADOS -</option>

              </select>
            </div>
        </div>
        <br>
        <br>
        <div class="row">

          <div class="col-xs-12">
              <table  style="border-collapse: collapse; table-layout:auto" align="center" class="table table-bordered" >
                <thead>
                  <tr  >
                    <th  class="col-xs-2" style="text-align:center !important;padding:0px;">
                      <h5  style="font-size: 13px; color:#000;text-align:center !important;">JUEGO</h5>
                    </th>
                    <th  class="col-xs-1" style="text-align:center !important;padding:0px;">
                      <h5  style="font-size: 13px; color:#000;text-align:center !important;">NRO MESA</h5>
                    </th>
                    <th  class="col-xs-2" style="text-align:center !important;padding:0px;">
                      <h5 style="font-size: 13px; color:#000;text-align:center !important;">DROP</h5>
                    </th>
                    <th  class="col-xs-2" style="text-align:center !important;padding:0px;">
                      <h5 style="font-size: 13px; color:#000;text-align:center !important;">UTILIDAD</h5>
                    </th>
                    <th  class="col-xs-2" style="text-align:center !important;padding:0px;">
                      <h5 style="font-size: 13px; color:#000;text-align:center !important;">RETIROS</h5>
                    </th>
                    <th  class="col-xs-2" style="text-align:center !important;padding:0px;padding:0px;">
                      <h5 style="font-size: 13px; color:#000;text-align:center !important;">REPOSICIONES</h5>
                    </th>
                    <th  class="col-xs-1" style="text-align:center !important;padding:0px;">
                      <h5 style="font-size: 13px; color:#000;text-align:center !important;">HOLD %</h5>
                    </th>
                  </tr>
                </thead>
                <tbody id="datosImpDiarios" >

                </tbody>
              </table>
              <div class="table table-responsive" id="mostrarTablaValidar"  style="display:none;">

                <table class="table" style="padding:0px !important">
                  <tbody>
                  <tr id="moldeImpDiarios" class="filaClone"  style="display:none">
                      <td class="col-xs-2 v_juego" style="text-align:center !important;padding:2px !important;"></td>
                      <td class="col-xs-1 v_mesa" style="text-align:center !important;padding:2px !important;"></td>
                      <td class="col-xs-2 v_drop" style="text-align:right !important;padding:2px !important;"></td>
                      <td class="col-xs-2 v_utilidad" style="text-align:right !important;padding:2px !important;"></td>
                      <td class="col-xs-2 v_retiros" style="text-align:right !important;padding:2px !important;"></td>
                      <td class="col-xs-2 v_reposiciones" style="text-align:right !important;padding:2px !important;"></td>
                      <td class="col-xs-1 v_hold" style="text-align:center !important;padding:2px !important;"></td>

                  </tr>
              </table>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12">
              <h5>OBSERVACIONES</h5>
              <textarea name="name" id="observacionesImpD" class="form-control col-xs-12"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-successAceptar" id="guardar-observacion" value="" hidden="true">VALIDAR</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<!-- modal ver info importación -->
<div class="modal fade" id="modalInfoImportacion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" style="width:60%">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #0D47A1;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| DETALLE IMPORTACIÓN DIARIA VALIDADA</h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row">
            <div class="col-xs-3">
              <h5>FECHA</h5>
              <input id="fechaInfo" class="form-control" type="text" value=""  size="100" autocomplete="off" readonly="true">

            </div>
            <div class="col-xs-3">
              <h5>CASINO</h5>
              <input id="casinoInfo" class="form-control" type="text" value=""  size="100" autocomplete="off" readonly="true">

            </div>
            <div class="col-xs-3">
              <h5>MONEDA</h5>
              <input id="monedaInfo" class="form-control" type="text" value=""  size="100" autocomplete="off" readonly="true">
            </div>
            <div class="col-xs-3">
              <h5>TIPO DE MESA</h5>
              <select class="form-control" name="" id="selectMesaInfo">
                <option value="1" selected>- RULETA -</option>
                <option value="2">- CARTAS -</option>
                <option value="3">- DADOS -</option>

              </select>
            </div>
        </div>
        <br>
        <br>
        <div class="row">

          <div class="col-xs-12">
              <table  style="border-collapse: collapse; table-layout:auto" align="center" class="table table-bordered table-responsive" >
                <thead>
                  <tr>
                    <th  class="col-xs-2" style="text-align:center !important;padding:0px;">
                      <h5  style="font-size: 13px; color:#000;text-align:center !important;">JUEGO</h5>
                    </th>
                    <th  class="col-xs-1" style="text-align:center !important;padding:0px;">
                      <h5  style="font-size: 13px; color:#000;text-align:center !important;">NRO MESA</h5>
                    </th>
                    <th  class="col-xs-2" style="text-align:center !important;padding:0px;">
                      <h5 style="font-size: 13px; color:#000;text-align:center !important;">DROP</h5>
                    </th>
                    <th  class="col-xs-2" style="text-align:center !important;padding:0px;">
                      <h5 style="font-size: 13px; color:#000;text-align:center !important;">UTILIDAD</h5>
                    </th>
                    <th  class="col-xs-2" style="text-align:center !important;padding:0px;">
                      <h5 style="font-size: 13px; color:#000;text-align:center !important;">RETIROS</h5>
                    </th>
                    <th  class="col-xs-2" style="text-align:center !important;padding:0px;padding:0px;">
                      <h5 style="font-size: 13px; color:#000;text-align:center !important;">REPOSICIONES</h5>
                    </th>
                    <th  class="col-xs-1" style="text-align:center !important;padding:0px;">
                      <h5 style="font-size: 13px; color:#000;text-align:center !important;">HOLD %</h5>
                    </th>
                  </tr>
                </thead>
                <tbody id="datosInfoDiarios" >

                </tbody>
              </table>
            <div class="table table-responsive" id="mostrarTablaver"  style="display:none;">

              <table class="table" style="padding:0px !important">
                <tbody>
                  <tr id="moldeInfoDiarios" class="filaClone">
                      <td class="col-xs-2 info_juego" style="padding:2px;text-align:center !important;"></td>
                      <td class="col-xs-1 info_mesa" style="padding:2px;text-align:center !important;"></td>
                      <td class="col-xs-2 info_drop" style="padding:2px;text-align:right !important;"></td>
                      <td class="col-xs-2 info_utilidad" style="padding:2px;text-align:right !important;"></td>
                      <td class="col-xs-2 info_retiros" style="padding:2px;text-align:right !important;"></td>
                      <td class="col-xs-2 info_reposiciones" style="padding:2px;text-align:right !important;"></td>
                      <td class="col-xs-1 info_hold" style="padding:2px;text-align:center !important;"></td>
                  </tr>
                </tbody>
              </table>
            </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12">
              <h5>OBSERVACIONES</h5>
              <textarea name="name" id="observacionesInfo" class="form-control col-xs-12" readonly="true"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-successAceptar" id="guardar-observacion-info" value="" hidden="true">GUARDAR</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL ELIMINAR -->
<div class="modal fade" id="modalAlertaEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
       <div class="modal-content">

         <div class="modal-header" style="background: #d9534f; color: #E53935;">
           <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
           <h3 class="modal-titleEliminar" style="color:#fff;">| ALERTA</h3>
         </div>
        <div class="modal-body" style="color:#fff; background-color:#FFFFF;">

              <h6 style="color:#000000 !important; font-size:17px !important;">¿ESTA SEGURO QUE DESEA ELIMINAR ESTA IMPORTACIÓN?</h6>
              <br>
              <h6 id="msjeliminarJuego" style="color:#000000 !important;font-size:14px;"></h6>

        </div>
        <br>
        <div class="modal-footer">
          <button type="button" class="btn btn-dangerEliminar" id="btn-eliminar" value="" data-dismiss="modal">ELIMINAR</button>
        </div>
    </div>
  </div>
</div>
<!-- FIN MODALES DIARIOS -->

<!-- modales Imp mensuales -->

<!-- Modal Importacion Mensual-->
<div class="modal fade" id="modalImportacionMensual" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" style="width: 60%">
         <div class="modal-content">
           <div class="modal-header" style="font-family: Roboto-Black; background-color: #6dc7be;">
             <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
             <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
             <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
             <h3 class="modal-title">| IMPORTACIÓN INFORME MENSUAL DE MESAS</h3>
            </div>

            <div  id="colapsado" class="collapse in">

            <div class="modal-body modalCuerpo">
                    <!-- Estilos del mansaje de información -->
                    <style media="screen">
                      #mensajeInvalido i {
                        color: #FF5252;
                        position: relative;
                        top: -3px;
                        left: -10px;
                        transform: scale(2);
                      }
                      #mensajeInvalido h6 {
                        margin-left: 6px !important;
                        color: #FF1744;
                        display:inline;
                        font-size: 20px;
                        font-weight:bold !important; font-family:Roboto-Condensed !important
                      }
                      #mensajeInvalido p {
                        /*color: black;*/
                        display:inline;
                        font-size: 16px;
                        /*font-weight:bold !important; */
                        font-family:Roboto-Regular !important
                      }

                      #iconoMoneda {
                        transform: scale(1.2);
                      }
                  </style>

              <form id="frmImportacion"  class="form-horizontal" novalidate="">
                <div class="col-xs-4 rowFecha">
                  <h5>FECHA*</h5>
                  <div class="form-group">
                    <div class='input-group date' id='dtpFechaImpMes' data-link-field="fecha_filtro" data-date-format="yyyy-MM" data-link-format="yyyy-MM">
                      <input type='text' class="form-control" id="B_fecha_imp_mes" value="" placeholder="aaaa-mm"/>
                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>

                </div>
                <div class="col-xs-4 rowCasino">
                  <h5>CASINO*</h5>
                  <select class="form-control" id="casinoSelMes">
                    <option value="0" selected>- Seleccione un Casino -</option>
                    @foreach ($casinos as $cas)
                    <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-xs-4 rowMoneda">
                  <h5>MONEDA*</h5>
                  <select class="form-control" id="monedaSelMes">
                    <option value="0" selected>- Seleccione Moneda -</option>
                    @foreach ($moneda as $mon)
                    <option value="{{$mon->id_moneda}}">{{$mon->descripcion}}</option>
                    @endforeach

                  </select>
                </div>
              </form>

              <div id="rowArchivoMes" class="row" style="">
                      <div class="col-xs-12">
                        <div class="zona-file">
                          <h5>ARCHIVO</h5>
                            <input id="archivoMes" data-borrado="false" type="file" name="" >
                            <br> <span id="alertaArchivo" class="alertaSpan"></span>
                        </div>
                      </div>
              </div>
              <div id="mensajeErrorMes" class="row" style="margin-bottom:20px !important; margin-top: 20px !important;">
                      <div class="col-md-12">
                          <h6>SE PRODUJO UN ERROR DE CONEXIÓN</h6>
                          <button id="btn-reintentarContador" class="btn btn-info" type="button" name="button">REINTENTAR IMPORTACIÓN</button>
                      </div>
                  </div>


              <div id="mensajeInvalido" class="row" style="margin-bottom:20px !important; margin-top: 20px !important;">
                  <div class="col-xs-12" align="center">
                            <i class="fa fa-fw fa-exclamation-triangle"></i>
                            <h6> ARCHIVO INCORRECTO</h6>
                        </div>
                        <br>
                        <br>
                        <div class="col-xs-12" align="center">
                            <p id="msjFilas">Solo se aceptan archivos con extensión .csv o .txt</p>
                        </div>
                  </div>

              <div class="loading" id="iconoCargaMes" style="text-align: center" hidden="true">
                  <img src="/img/ajax-loader(1).gif" alt="loading" />
                  <br>Un momento, por favor...
              </div>
            </div>
            <div class="modal-footer">
              <span style="font-family:sans-serif;float:left !important;font-size:12px;color:#0D47A1"> * Campos Obligatorios</span>
              <button type="button" class="btn btn-successAceptar" id="btn-guardarMensual" value="nuevo"> SUBIR</button>
              <button type="button" class="btn btn-default" data-dismiss="modal"> CANCELAR</button>
            </div>
          </div>
        </div>
      </div>
</div>

<!-- Modal ver detalles importados -->
<div class="modal fade" id="modalInfoMensual" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" style="width:60%">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #0D47A1;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| DETALLE IMPORTACIÓN MENSUAL</h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row"  style=" border-bottom:1px solid #ccc">
            <div class="col-xs-4" >
              <h6 class="list-group-item"  style="font-size:16px !important; text-align:center !important; background-color:#aaa; color:white;">MES</h6>
              <h6 class="list-group-item" style="text-align:center !important; margin-top:0px !important; font-size:14px !important" id="fechaImpM"></h6>
            </div>
            <div class="col-xs-4">
              <h6 class="list-group-item"  style=" font-size:16px !important; text-align:center !important; background-color:#aaa; color:white;">CASINO:</h6>
              <h6 class="list-group-item" style="margin-top:0px !important; text-align:center !important; font-size:14px !important" id="casinoImpM"></h6>
            </div>
            <div class="col-xs-4">
              <h6 class="list-group-item"  style=" font-size:16px !important; text-align:center !important; background-color:#aaa; color:white;">MONEDA:</h6>
              <h6 class="list-group-item" style="margin-top:0px !important; text-align:center !important; font-size:14px !important" id="monedaImpM"></h6>
            </div>
        </div>
        <br>
        <div class="row">

            <div class="col-xs-12" >

              <table  style="border-collapse: collapse; table-layout:auto" align="center" class="table table-bordered" >
                  <thead>
                    <tr>
                      <th class="col-xs-2" style="text-align:center !important;padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;">
                        <h5  style="font-size: 13px; color:#000;text-align:center !important;">FECHA</h5>
                      </th>
                      <th class="col-xs-2" style="text-align:center !important;padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;">
                        <h5 style="font-size: 13px; color:#000;text-align:center !important;">DROP</h5>
                      </th>
                      <th class="col-xs-2" style="text-align:center !important;padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;">
                        <h5 style="font-size: 13px; color:#000;text-align:center !important;">UTILIDAD</h5>
                      </th>
                      <th class="col-xs-2" style="text-align:center !important;padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;">
                        <h5 style="font-size: 13px; color:#000;text-align:center !important;">RETIROS</h5>
                      </th>
                      <th class="col-xs-2" style="text-align:center !important;padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;">
                        <h5 style="font-size: 13px; color:#000;text-align:center !important;">REPOSICIONES</h5>
                      </th>
                      <th class="col-xs-2" style="text-align:center !important;padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;">
                        <h5 style="font-size: 13px; color:#000;text-align:center !important;">HOLD %</h5>
                      </th>
                    </tr>
                  </thead>
                  <tbody id="datosMensuales" >

                  </tbody>
                </table>
                <div class="table table-responsive" id="mostrarTablaVerMensual"  style="display:none;">

                  <table class="table" style="padding:0px !important">
                    <tr id="moldeInfoMensual" class="filaClone" >
                      <td class="col-xs-2 ver_fecha" style="padding:2px;text-align:center !important;"></td>
                      <td class="col-xs-2 ver_drop" style="padding:2px;text-align:right !important;"></td>
                      <td class="col-xs-2 ver_utilidad" style="padding:2px;text-align:right !important;"></td>
                      <td class="col-xs-2 ver_retiros" style="padding:2px;text-align:right !important;"></td>
                      <td class="col-xs-2 ver_reposiciones" style="padding:2px;text-align:right !important;"></td>
                      <td class="col-xs-2 ver_hold" style="padding:2px;text-align:center !important;"></td>
                    </tr>
                  </tbody>
                </table>
              </div>
              </div>

          </div>
          <div class="row">
            <div class="col-md-12">
              <h5>OBSERVACIONES</h5>
              <textarea name="name" id="observacionesImpM" class="form-control col-xs-12"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
          </div>
          <div id="mensajeErrorGral" hidden>
              <br>
              <div class="col-xs-12" style="display: inline-block;">
                  <i class="fa fa-fw fa-exclamation-triangle" style="color:#C62828;display: inline-block;" ></i>
                  <h6 style="display: inline-block;"> ARCHIVO INCORRECTO</h6>
              </div>
              <br>
              <p id="span2" style="font-family:'Roboto-Regular'; font-size:16px; color:#555;"></p>
          </div> <!-- mensaje -->
        </div>
      </div>
    </div>
  </div>
</div>

<!-- modal Validar -->
<div class="modal fade" id="modalValidarMensual" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" style="width:60%">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">|  VALIDACIÓN IMPORTACIÓN MENSUAL</h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row">
            <div class="col-xs-4">
              <h5>MES</h5>
              <input id="fechaValidar" class="form-control" type="text" value=""  size="100" autocomplete="off" readonly="true">

            </div>
            <div class="col-xs-4">
              <h5>CASINO</h5>
              <input id="casinoValidar" class="form-control" type="text" value=""  size="100" autocomplete="off" readonly="true">

            </div>
            <div class="col-xs-4">
              <h5>MONEDA</h5>
              <input id="monedaValidar" class="form-control" type="text" value=""  size="100" autocomplete="off" readonly="true">
            </div>

        </div>
        <br>
        <br>
        <div class="row">

            <div class="col-xs-12" >

                <table   style="border-collapse: collapse; table-layout:auto; overflow:scroll;" align="center" class=" table table-fixed"  >
                  <thead>
                    <tr>
                      <th class="col-xs-3" style="text-align:center !important;padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;">
                        <h5  style="font-size: 15px; color:#000;text-align:center !important;">FECHA</h5>
                      </th>
                      <th class="col-xs-3" style="text-align:center !important;padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;">
                        <h5 style="font-size: 15px; color:#000;text-align:center !important;">DROP</h5>
                      </th>
                      <th class="col-xs-3" style="text-align:center !important;padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;">
                        <h5 style="font-size: 15px; color:#000;text-align:center !important;">UTILIDAD</h5>
                      </th>
                      <th class="col-xs-3" style="text-align:center !important;padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;">
                        <h5 style="font-size: 15px; color:#000;text-align:center !important;">HOLD %</h5>
                      </th>
                    </tr>
                  </thead>
                  <tbody id="datosMensualesValidar" >

                  </tbody>
                </table>
                <table style="table-layout:auto" class="table table-fixed">
                  <tbody>
                    <tr id="moldeValidarMensual" class="filaClone" style="display:none">
                      <td class="col-xs-3 validar_fecha" style="text-align:center !important;"></td>
                      <td class="col-xs-3 validar_drop" style="text-align:right !important;"></td>
                      <td class="col-xs-3 validar_utilidad" style="text-align:right !important;"></td>
                      <td class="col-xs-3 validar_hold" style="text-align:center !important;"></td>
                    </tr>
                  </tbody>
                </table>
              </div>

          </div>
          <div class="row">
            <div class="col-md-12">
              <h5>OBSERVACIONES</h5>
              <textarea name="name" id="observacionesValidar" class="form-control col-xs-12"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-successAceptar" id="validarMes" value="" hidden="true">VALIDAR</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- FIN MODALES MENSUALES -->

<meta name="_token" content="{!! csrf_token() !!}" />

@endsection

@section('scripts')

  <!-- JavaScript personalizado -->
  <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
  <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

  <script src="js/inputSpinner.js" type="text/javascript"></script>
  <script src="/js/lista-datos.js" type="text/javascript"></script>

  <script src="js/fileinput.min.js" type="text/javascript"></script>

  <script src="/js/locales/es.js" type="text/javascript"></script>
  <script src="/themes/explorer/theme.js" type="text/javascript"></script>

  <script src="js/math.min.js" type="text/javascript"></script>

    <script src="/js/paginacion.js" charset="utf-8"></script>
    <script src="js/Importaciones/ImportacionDiaria.js" charset="utf-8"></script>
    <script src="js/Importaciones/importacionMensual.js" charset="utf-8"></script>


@endsection
