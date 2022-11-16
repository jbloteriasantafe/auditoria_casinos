@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')
<?php
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
setlocale(LC_TIME, 'es_ES.UTF-8');
$id_usuario = session('id_usuario');
?>

@section('estilos')
<link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/animacionCarga.css">
<link rel="stylesheet" href="/css/paginacion.css">
@endsection

          <div class="row">

              <!-- columna de los BOTONES  -->
              <div class="col-md-3">
                    <!-- IMPORTAR CONTADORES -->
                    <div class="row">
                        <div class="col-sm-12 col-md-12 col-xl-12">
                         <a href="" id="btn-importarContadores" style="text-decoration: none;">
                          <div class="panel panel-default panelBotonNuevo">
                              <center><img class="imgNuevo" src="/img/logos/CSV_white.png"><center>
                              <div class="backgroundNuevo"></div>
                              <div class="row">
                                  <div class="col-xs-12">
                                    <center>
                                        <h5 class="txtLogo">+<span style="font-size:145px; position:relative; top:-8px;">C</span></h5>
                                        <h4 class="txtNuevo">IMPORTAR CONTADORES</h4>
                                    </center>
                                  </div>
                              </div>
                          </div>
                         </a>
                        </div>
                    </div> <!--   .row | IMPORTAR CONTADORES -->

                    <!-- IMPORTAR PRODUCIDOS -->
                    <div class="row">
                        <div class="col-sm-12 col-md-12 col-xl-12">
                         <a href="" id="btn-importarProducidos" style="text-decoration: none;">
                          <div class="panel panel-default panelBotonNuevo">
                              <center><img class="imgNuevo" src="/img/logos/CSV_white.png"><center>
                              <div class="backgroundNuevo"></div>
                              <div class="row">
                                  <div class="col-xs-12">
                                    <center>
                                        <h5 class="txtLogo">+<span style="font-size:145px; position:relative; top:-8px;">P</span></h5>
                                        <h4 class="txtNuevo">IMPORTAR PRODUCIDOS</h4>
                                    </center>
                                  </div>
                              </div>
                          </div>
                         </a>
                        </div>
                    </div> <!--    .row | IMPORTAR PRODUCIDOS -->

                    <!-- IMPORTAR BENEFICIOS -->
                    <div class="row">
                        <div class="col-sm-12 col-md-12 col-xl-12">
                         <a href="" id="btn-importarBeneficios" style="text-decoration: none;">
                          <div class="panel panel-default panelBotonNuevo">
                              <center><img class="imgNuevo" src="/img/logos/CSV_white.png"><center>
                              <div class="backgroundNuevo"></div>
                              <div class="row">
                                  <div class="col-xs-12">
                                    <center>
                                        <h5 class="txtLogo">+<span style="font-size:145px; position:relative; top:-8px;">B</span></h5>
                                        <h4 class="txtNuevo">IMPORTAR BENEFICIOS</h4>
                                    </center>
                                  </div>
                              </div>
                          </div>
                         </a>
                        </div>
                    </div> <!-- .row | IMPORTAR BENEFICIOS -->
                    <div class="row">
                      <div class="col-sm-12 col-md-12 col-xl-12">
                        <a href="/generar_beneficio_sfemel.html" target="_blank" style="background: white;font-weight: bold;padding: 2px;">
                          GENERAR BENEFICIO CON PRODUCIDOS (SFE/MEL)
                        </a>
                      </div>
                    </div>
                    <br>
              </div> <!-- .columna de los BOTONES -->


              <!-- tabla info -->
              <div class="col-md-9">
                  <div class="row">
                      <div class="col-md-12">
                          <div class="panel panel-default" style="height:644px;">
                              <div class="panel-heading">
                                  <!-- <h4 style="font-size:18px;font-family:Roboto-Condensed;color:black;">IMPORTACIONES POR DÍA</h4> -->
                                  <h4>IMPORTACIONES POR DÍA</h4>
                              </div>
                              <div class="panel-body">
                                  <div class="row">
                                      <div class="col-md-3">
                                        <select id="casinoInfoImportacion" class="form-control" name="">
                                            @foreach ($casinos as $casino)
                                              <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                            @endforeach
                                        </select>
                                      </div>
                                      <div class="col-md-3">
                                        <div class='input-group date' id='mesInfoImportacion' data-link-field="mes_info_hidden" data-date-format="dd MM yyyy" data-link-format="yyyy-mm-dd">
                                            <input type='text' class="form-control"/>
                                            <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                            <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                        </div>
                                        <input type="hidden" id="mes_info_hidden" value=""/>
                                      </div>
                                      <div class="col-md-3">
                                        <select id="monedaInfoImportacion" class="form-control" name="">
                                            @foreach($tipoMoneda as $tipo)
                                              <option value="{{$tipo->id_tipo_moneda}}">{{$tipo->descripcion}}</option>
                                            @endforeach
                                        </select>
                                      </div>
                                  </div>




                                  <br>

                                  <style media="screen">
                                      #infoImportaciones .fa-check {
                                          color: #00C853;
                                      }
                                      #infoImportaciones .fa-times {
                                          color: #FF1744;
                                      }
                                      #infoImportaciones td {
                                          height: 50px;
                                      }

                                      #infoImportaciones td.true .fa-check {
                                        display: inline;
                                      }

                                      #infoImportaciones td.true .fa-times {
                                        display: none;
                                      }

                                      #infoImportaciones td.false .fa-times {
                                        display: inline;
                                      }

                                      #infoImportaciones td.false .fa-check {
                                        display: none;
                                      }

                                      /* Bordes para identificar el casino en cada tabla */

                                      #bodySantaFe {
                                        border-top: 3px solid #EF9A9A !important;
                                      }
                                      #bodyMelincue {
                                        border-top: 3px solid #A5D6A7 !important;
                                      }
                                      #bodyRosario.pesos {
                                        border-top: 3px solid #FFE0B2 !important;
                                      }
                                      #bodyRosario.dolares {
                                        border-top: 3px solid #FFE082 !important;
                                      }
                                  </style>

                                  <table id="infoImportaciones" class="table table-fixed tablesorter">
                                      <thead id="headSantaFe">
                                        <tr>
                                          <th class="col-xs-3" value="fecha" estado="">FECHA<i class="fa fa-sort"></i></th>
                                          <th class="col-xs-3" style="text-align:center;">CONTADORES </th>
                                          <th class="col-xs-3" style="text-align:center;">PRODUCIDOS</th>
                                          <th class="col-xs-3" style="text-align:center;">BENEFICIOS</th>
                                        </tr>
                                      </thead>
                                      <!-- Se usa como molde para generar todas las filas -->
                                      <tbody id="bodySantaFe" class="tablaBody" style="text-align:center; max-height:440px;">
                                        <tr id="moldeFilaImportacion">
                                          <td class="col-xs-3 fecha" style="text-align:left;">12 AGO 2018</td>
                                          <td class="col-xs-3 contador"><i class="fa fa-check"></i><i class="fa fa-times"></i></td>
                                          <td class="col-xs-3 producido"><i class="fa fa-check"></i><i class="fa fa-times"></i></td>
                                          <td class="col-xs-3 beneficio"><i class="fa fa-check"></i><i class="fa fa-times"></i></td>
                                        </tr>
                                      </tbody>
                                      <tbody id="bodyMelincue" class="tablaBody" style="text-align:center; max-height:440px;">

                                      </tbody>
                                      <tbody id="bodyRosario" class="tablaBody" style="text-align:center; max-height:440px;">

                                      </tbody>
                                  </table>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>

              <!-- columna FILTROS -->
              <div class="col-md-12">
                <div class="row">
                    <div class="col-lg-12">
                      <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4>FILTROS DE BÚSQUEDA</h4>
                        </div>
                        <div class="panel-body" id="filtrosBusquedaImportaciones">
                          <div class="row">

                              <div class="col-md-3">
                                  <h5>TIPO DE ARCHIVO</h5>
                                  <select id="tipo_archivo" class="form-control">
                                          <option value="1">CONTADORES</option>
                                          <option value="2">PRODUCIDOS</option>
                                          <option value="3">BENEFICIOS</option>
                                  </select>
                              </div>
                              <div class="col-md-2">
                                  <h5>CASINO</h5>
                                  <select id="casino_busqueda" class="form-control">
                                    <option value="0">Todos los casinos</option>
                                      @foreach ($casinos as $casino)
                                        <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                      @endforeach
                                  </select>
                              </div>
                              <div class="col-md-3">
                                  <h5>FECHA</h5>
                                  <div class='input-group date' id='fecha_busqueda' data-link-field="fecha_busqueda_hidden" data-date-format="dd MM yyyy" data-link-format="yyyy-mm-dd">
                                      <input type='text' class="form-control" placeholder="Fecha de Inicio"/>
                                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                  </div>
                                  <input type="hidden" id="fecha_busqueda_hidden" value=""/>
                              </div>
                              <div class="col-md-2">
                                  <h5>MONEDA</h5>
                                  <select id="moneda_busqueda" class="form-control">
                                    <option value="0">Todos las monedas</option>
                                    @foreach($tipoMoneda as $tipo)
                                      <option value="{{$tipo->id_tipo_moneda}}">{{$tipo->descripcion}}</option>
                                    @endforeach
                                  </select>
                              </div>
                              <div class="col-md-2">
                                  <h5 style="color:#ffffff;">búsqueda</h5>
                                  <button id="btn-buscarImportaciones" class="btn btn-infoBuscar" type="button">
                                    <i class="fa fa-fw fa-search"></i> BUSCAR
                                  </button>
                              </div>
                          </div> <!-- row -->
                        </div> <!-- panel-body -->
                      </div>
                    </div>
                </div>
              </div> <!-- columna tabla -->

              <!-- columna TABLA -->
              <div class="col-md-12">

                  <!-- TABLA -->
                  <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                          <div class="panel-heading">
                            <h4 id="tituloTabla">CONTADORES</h4>
                              <!-- <div id="tituloTabla"></div> -->
                          </div>
                          <div class="panel-body">

                            <table id="tablaImportaciones" class="table table-fixed tablesorter">
                                <thead>
                                  <tr>
                                    <th class="col-xs-3" value="contador_horario.fecha" estado="">FECHA PRODUCCIÓN<i class="fa fa-sort"></i></th>
                                    <th id="tipo_fecha" class="col-xs-3 activa" value="contador_horario.fecha" estado="desc">FECHA <i class="fa fa-sort-desc"></i></th>
                                    <th class="col-xs-2" value="casino.nombre" estado="">CASINO <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2" value="tipo_moneda.descripcion" estado="">MONEDA <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2" value="" estado="">ACCIÓN</th>
                                  </tr>
                                </thead>
                                <tbody style="height: 300px;">
                                </tbody>
                            </table>

                            <br>

                            <legend></legend>
                            <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                          </div> <!-- .panel-body -->
                        </div> <!-- .panel -->
                    </div> <!-- .col-lg-12 -->
                  </div> <!-- .row | TABLA -->

              </div> <!-- .col-lg-9 -->

          </div> <!-- .row -->


    <!-- Modal planilla -->
    <div class="modal fade" id="modalPlanilla" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header" style="background: #4FC3F7">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-title">VISTA PREVIA</h3>
                </div>

                <div class="modal-body">
                  <div class="row">
                      <div class="col-md-4">
                          <h5>FECHA</h5>
                          <input id="fecha" class="form-control" type="text" value="" disabled>
                      </div>
                      <div class="col-md-4">
                          <h5>CASINO</h5>
                          <input id="casino" class="form-control" type="text" value="" disabled>
                      </div>
                      <div class="col-md-4">
                          <h5>TIPO MONEDA</h5>
                          <input id="tipo_moneda" class="form-control" type="text" value="" disabled>
                      </div>
                  </div>
                  <br>
                  <div class="row">
                      <div class="col-md-12">
                          <table id="tablaVistaPrevia" class="table table-fixed">
                            <thead>
                                <tr>

                                </tr>
                            </thead>
                            <tbody style="max-height:400px;">

                            </tbody>
                          </table>
                          <br>
                          <p style="color:#aaa;font-family:Roboto-Regular;font-size:16px;margin-left:10px;">Se muestran los primeros 30 registros.</p>
                      </div>
                  </div>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
                </div>
            </div>
          </div>
    </div>

    <!-- Modal Eliminar -->
    <div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header" style="font-family: Roboto-Black; color: #EF5350;">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-titleEliminar">ADVERTENCIA</h3>
                </div>

                <div class="modal-body franjaRojaModal">
                  <form id="frmEliminar" name="frmCasino" class="form-horizontal" novalidate="">
                      <div class="form-group error ">
                          <div class="col-xs-12">
                            <strong id="titulo-modal-eliminar">¿Seguro que desea eliminar la importación?</strong>
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

    <!-- Modal Importacion -->
    <div class="modal fade" id="modalImportacionContadores" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
               <div class="modal-header modalNuevo" style="font-family: Roboto-Black; background-color: #6dc7be;">
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">| IMPORTADOR CONTADOR</h3>
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
                      </style>

                  <div id="rowArchivo" class="row" style="">
                    <div class="col-xs-12">
                        <h5>ARCHIVO</h5>
                        <div class="zona-file">
                          <input id="archivo" data-borrado="false" type="file" name="" >
                          <br> <span id="alertaArchivo" class="alertaSpan"></span>
                        </div>
                    </div>
                    @include('includes.md5hash')
                  </div>

                  <div class="row" id="valoresArchivoContador">
                    <div class="row">
                      <div class="col-xs-5">
                        <h5>FECHA</h5>
                        <div class='input-group date' id='fecha' data-link-field="fecha_hidden" data-date-format="dd/mm/yyyy" data-link-format="yyyy-mm-dd">
                          <input type='text' class="form-control" placeholder="Fecha de Inicio"/>
                          <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                          <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                        </div>
                        <input type="hidden" id="fecha_hidden" value=""/>
                      </div>
                      <div class="col-xs-4">
                        <h5>CASINO</h5>
                        <select id="contSelCasino" class="form-control">
                          <option value="-1">Seleccione</option>
                          @foreach ($casinos as $casino)
                          <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-xs-3">
                        <h5>MONEDA</h5>
                        <select id="contSelMoneda" class="form-control">
                          <option value="-1">Seleccione</option>
                          @foreach($tipoMoneda as $tipo)
                          <option value="{{$tipo->id_tipo_moneda}}">{{$tipo->descripcion}}</option>
                          @endforeach
                        </select>
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
                  <div id="iconoCarga" class="sk-folding-cube">
                    <div class="sk-cube1 sk-cube"></div>
                    <div class="sk-cube2 sk-cube"></div>
                    <div class="sk-cube4 sk-cube"></div>
                    <div class="sk-cube3 sk-cube"></div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-guardarContador" hidden value="nuevo"> SUBIR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal"> CANCELAR</button>
                  <input type="hidden" id="tipoImportacion" name="" value="">
                </div>
              </div>
            </div>
          </div>
    </div>

    <!-- Modal Producido -->
    <div class="modal fade" id="modalImportacionProducidos" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
               <div class="modal-header modalNuevo" style="font-family: Roboto-Black; background-color: #6dc7be;">
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizarProducidos" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoProducidos" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">| IMPORTAR PRODUCIDOS</h3>
                </div>

                <div  id="colapsadoProducidos" class="collapse in">

                <div class="modal-body modalCuerpo">

                  <div id="rowArchivo" class="row" style="">
                    <div class="col-xs-12">
                        <h5>ARCHIVO</h5>
                        <div class="zona-file">
                          <input id="archivo" data-borrado="false" type="file" name="" >
                          <br> <span id="alertaArchivo" class="alertaSpan"></span>
                        </div>
                    </div>
                    @include('includes.md5hash')
                  </div>

                  <div id="mensajeError" class="row" style="margin-bottom:20px !important; margin-top: 20px !important;">
                          <div class="col-md-12">
                              <h6>SE PRODUJO UN ERROR DE CONEXIÓN</h6>
                              <button id="btn-reintentarProducido" class="btn btn-info" type="button" name="button">REINTENTAR IMPORTACIÓN</button>
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

                  <div id="iconoCarga" class="sk-folding-cube">
                    <div class="sk-cube1 sk-cube"></div>
                    <div class="sk-cube2 sk-cube"></div>
                    <div class="sk-cube4 sk-cube"></div>
                    <div class="sk-cube3 sk-cube"></div>
                  </div>

                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-guardarProducido" hidden value="nuevo"> SUBIR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal"> CANCELAR</button>
                  <input type="hidden" id="tipoImportacion" name="" value="">
                </div>
              </div>
            </div>
          </div>
    </div>

    <!-- Modal Beneficio -->
    <div class="modal fade" id="modalImportacionBeneficios" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
               <div class="modal-header modalNuevo" style="font-family: Roboto-Black; background-color: #6dc7be;">
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizarBeneficios" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoBeneficios" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">| IMPORTAR BENEFICIOS</h3>
                </div>

                <div  id="colapsadoBeneficios" class="collapse in">

                <div class="modal-body modalCuerpo">


                  <div id="rowArchivo" class="row" style="">
                          <div class="col-xs-12">
                              <h5>ARCHIVO</h5>
                              <div class="zona-file">
                                <input id="archivo" data-borrado="false" type="file" name="" >
                                <br> <span id="alertaArchivo" class="alertaSpan"></span>
                              </div>
                          </div>
                          @include('includes.md5hash')
                  </div>

                  <div id="rowMoneda" hidden class="row" style="margin-bottom:20px !important; margin-top: 20px !important;">
                          <div class="col-xs-6">
                              <h5>MONEDA</h5>
                              <select class="form-control" name="">
                                  <option value="0">Elegir moneda</option>
                                  <option value="1">ARS</option>
                                  <option value="2">USD</option>
                              </select>
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

                  <div id="iconoCarga" class="sk-folding-cube">
                    <div class="sk-cube1 sk-cube"></div>
                    <div class="sk-cube2 sk-cube"></div>
                    <div class="sk-cube4 sk-cube"></div>
                    <div class="sk-cube3 sk-cube"></div>
                  </div>

                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-guardarBeneficio" hidden value="nuevo"> SUBIR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal"> CANCELAR</button>
                  <input type="hidden" id="tipoImportacion" name="" value="">
                </div>
              </div>
            </div>
          </div>
    </div>

    <!-- Modal ErrorVisado -->
    <div class="modal fade" id="modalErrorVisado" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
           <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h3 class="modal-titleEliminar">DENEGADO</h3>
              </div>

              <div class="modal-body franjaRojaModal">
                <p>No es posible importar contadores ya que existen relevamientos visados para el casino y fecha seleccionado</p>
              </div>

              <div class="modal-footer">
                
                <button type="button" class="btn btn-default" data-dismiss="modal">ACEPTAR</button>
              </div>
          </div>
        </div>
  </div>

    

    
    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| IMPORTACIONES</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Importaciones</h5>
      <p>
        A simple vista podrán verse por fecha si los contadores, producidos y beneficios fueron importados correctamente, en tiempo y forma.
        Luego, se lograrán las importaciones de cargas en formato .csv de los sistemas del concesionario. Para fechas anteriores que muestra a primera vista
        el sistema, existen filtros de búsqueda para su obtención.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript personalizado -->
    <script src="js/seccionImportaciones.js?3" charset="utf-8"></script>
    <script src="js/lib/spark-md5.js?2" charset="utf-8"></script><!-- Dependencia de md5.js -->
    <script src="js/md5.js?2" charset="utf-8"></script>

    <!-- JS paginacion -->
    <script src="/js/paginacion.js" charset="utf-8"></script>

    <!-- Custom input Bootstrap -->
    <script src="js/fileinput.min.js" type="text/javascript"></script>
    <script src="js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>

    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>
    @endsection
