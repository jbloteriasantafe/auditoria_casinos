@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

<?php
use Illuminate\Http\Request;
use App\Http\Controllers\UsuarioController;
use\App\http\Controllers\RelevamientoAmbientalController;
$usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
?>

@section('estilos')
<link rel="stylesheet" href="/css/paginacion.css">
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/animacionCarga.css">

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

.smalltext{
  font-size: 95%;
}
</style>
@endsection

@section('contenidoVista')

    <div class="col-xl-9">

      <!-- FILTROS DE BÚSQUEDA -->
      <div class="row">
          <div>
              <div id="contenedorFiltros" class="panel panel-default" style="width: 100%">
                <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                  <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
                </div>
                <div id="collapseFiltros" class="panel-collapse collapse">
                  <div class="panel-body">
                    <div class="row">
                        <div class="col-md-2">
                            <h5>Casino</h5>
                            <select id="buscadorCasino" class="form-control">
                                <option value="">-Todos los Casinos-</option>
                                @foreach ($casinos as $casino)
                                  <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}" data-codigo="{{$casino->codigo}}">{{$casino->nombre}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <h5>Estado</h5>
                            <select id="buscadorEstado" class="form-control">
                              <option selected="" value="">- Todos los estados -</option>
                              @foreach ($estados_autoexclusion as $estado)
                                <option id="{{$estado->id_nombre_estado}}" value="{{$estado->id_nombre_estado}}">{{$estado->descripcion}}</option>
                              @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <h5>Apellido</h5>
                            <input class="form-control" id="buscadorApellido" value=""/>
                        </div>
                        <div class="col-md-2">
                          <h5>Día semanal</h5>
                          <select id="buscadorDia" class="form-control">
                            <option value="">- Todos los días -</option>
                            <option value="1">Lunes</option>
                            <option value="2">Martes</option>
                            <option value="3">Miercoles</option>
                            <option value="4">Jueves</option>
                            <option value="5">Viernes</option>
                            <option value="6">Sábado</option>
                            <option value="7">Domingo</option>
                          </select>
                        </div>
                        <div class="col-md-2">
                          <h5>Rango etario</h5>
                          <div class="input-group">
                              <input id="buscadorRangoEtarioD" class="form-control input-sm" value=""/>
                              <span class="input-group-btn" style="width:0px;"></span>
                              <input id="buscadorRangoEtarioH" class="form-control input-sm" value=""/>
                          </div>
                        </div>
                    </div>
                    <div class="row">
                      <div class="col-md-3">
                          <h5>DNI</h5>
                          <input class="form-control" id="buscadorDni" value=""/>
                      </div>
                      <div class="col-md-3">
                          <h5>Sexo</h5>
                          <select id="buscadorSexo" class="form-control" name="">
                            <option selected="" value="">- Todos -</option>
                            <option id="0" value="0">Masculino</option>
                            <option id="1" value="1">Femenino</option>
                            <option id="-1" value="-1">Otro</option>
                          </select>
                      </div>
                      <div class="col-md-3">
                          <h5>Localidad</h5>
                          <input class="form-control" id="buscadorLocalidad" value=""/>
                      </div>
                      <div class="col-md-3">
                          <h5>Provincia</h5>
                          <input class="form-control" id="buscadorProvincia" value=""/>
                      </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                          <h5>Fecha de autoexclusión - Desde</h5>
                          <div class="input-group date" id="dtpFechaAutoexclusionD">
                              <input type="text" class="form-control" placeholder="Fecha de autoexclusión (desde)" id="buscadorFechaAutoexclusionD" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                              <span id="input-times-autoexclusionD" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                              <span id="input-calendar-autoexclusionD" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <h5>Fecha de autoexclusión- Hasta</h5>
                          <div class="input-group date" id="dtpFechaAutoexclusionH">
                              <input type="text" class="form-control" placeholder="Fecha de autoexclusión (hasta)" id="buscadorFechaAutoexclusionH" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                              <span id="input-times-autoexclusionH" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                              <span id="input-calendar-autoexclusionH" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <h5>Fecha de vencimiento - Desde</h5>
                          <div class="input-group date" id="dtpFechaVencimientoD">
                              <input type="text" class="form-control" placeholder="Fecha de vencimiento (desde)" id="buscadorFechaVencimientoD" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                              <span id="input-times-vencimientoD" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                              <span id="input-calendar-vencimientoD" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <h5>Fecha de vencimiento - Hasta</h5>
                          <div class="input-group date" id="dtpFechaVencimientoH">
                              <input type="text" class="form-control" placeholder="Fecha de vencimiento (hasta)" id="buscadorFechaVencimientoH" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                              <span id="input-times-vencimientoH" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                              <span id="input-calendar-vencimientoH" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                          </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                          <h5>Fecha de revocación - Desde</h5>
                          <div class="input-group date" id="dtpFechaRevocacionD">
                              <input type="text" class="form-control" placeholder="Fecha de revocación (desde)" id="buscadorFechaRevocacionD" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                              <span id="input-times-revocacionD" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                              <span id="input-calendar-revocacionD" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <h5>Fecha de revocación - Hasta</h5>
                          <div class="input-group date" id="dtpFechaRevocacionH">
                              <input type="text" class="form-control" placeholder="Fecha de revocación (hasta)" id="buscadorFechaRevocacionH" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                              <span id="input-times-revocacionH" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                              <span id="input-calendar-revocacionH" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <h5>Fecha de cierre - Desde</h5>
                          <div class="input-group date" id="dtpFechaCierreD">
                              <input type="text" class="form-control" placeholder="Fecha de cierre AE (desde)" id="buscadorFechaCierreD" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                              <span id="input-times-cierreD" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                              <span id="input-calendar-cierreD" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <h5>Fecha de cierre - Hasta</h5>
                          <div class="input-group date" id="dtpFechaCierreH">
                              <input type="text" class="form-control" placeholder="Fecha de cierre AE (hasta)" id="buscadorFechaCierreH" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                              <span id="input-times-cierreH" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                              <span id="input-calendar-cierreH" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                          </div>
                        </div>
                  </div>
                  <div class="row">
                      <div class="col-md-2">
                        <h5>Encuesta</h5>
                        <select id="buscadorEncuesta" class="form-control">
                          <option selected="" value="">- Todos -</option>
                          <option value="1">Sí</option>
                          <option value="0">No</option>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <h5>Frecuencia</h5>
                        <select id="buscadorFrecuencia" class="form-control encuesta">
                          <option selected="" value="">- Todos -</option>
                          @foreach($frecuencias as $f)
                          <option value="{{$f->id_frecuencia}}">{{$f->nombre}}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-md-2">
                        <h5>Veces</h5>
                        <div class="input-group">
                          <input type="checkbox" class="form-control input-sm encuesta no_contesta" id="nc_veces" style="width: 5%;" title="NO CONTESTA">
                          <input id="buscadorVecesD" class="form-control input-sm encuesta" style="width: 30%;"  value=""/>
                          <input id="buscadorVecesH" class="form-control input-sm encuesta" style="width: 30%;" value=""/>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <h5>Horas</h5>
                        <div class="input-group">
                          <input type="checkbox" class="form-control input-sm encuesta no_contesta" id="nc_horas" style="width: 5%;" title="NO CONTESTA">
                          <input id="buscadorHorasD" class="form-control input-sm encuesta" style="width: 30%;"  value=""/>
                          <input id="buscadorHorasH" class="form-control input-sm encuesta" style="width: 30%;" value=""/>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <h5>Compañia</h5>
                        <select id="buscadorCompania" class="form-control encuesta">
                          <option selected="" value="">- Todos -</option>
                          <option value="0">Solo</option>
                          <option value="1">Acompañado</option>
                          <option value="-1">No contesta</option>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <h5>Juego</h5>
                        <select id="buscadorJuego" class="form-control encuesta">
                          <option selected="" value="">- Todos -</option>
                          @foreach($juegos as $j)
                          <option value="{{$j->id_juego_preferido}}">{{$j->nombre}}</option>
                          @endforeach
                        </select>
                      </div>
                  </div>
                  <div class="row">
                      <div class="col-md-2">
                        <h5>Conoce programa juego responsable</h5>
                        <select id="buscadorJuegoResponsable" class="form-control encuesta">
                          <option selected="" value="">- Todos -</option>
                          <option value="SI">Sí</option>
                          <option value="NO">No</option>
                          <option value="-1">No contesta</option>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <h5>Socio Club Jugadores</h5>
                        <select id="buscadorClub" class="form-control encuesta">
                          <option selected="" value="">- Todos -</option>
                          <option value="SI">Sí</option>
                          <option value="NO">No</option>
                          <option value="-1">No contesta</option>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <h5>Autocontrol</h5>
                        <select id="buscadorAutocontrol" class="form-control encuesta">
                          <option selected="" value="">- Todos -</option>
                          <option value="SI">Sí</option>
                          <option value="NO">No</option>
                          <option value="-1">No contesta</option>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <h5>Recibir información</h5>
                        <select id="buscadorRecibirInfo" class="form-control encuesta">
                          <option selected="" value="">- Todos -</option>
                          <option value="SI">Sí</option>
                          <option value="NO">No</option>
                          <option value="-1">No contesta</option>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <h5>Medio</h5>
                        <select id="buscadorMedio" class="form-control encuesta">
                          <option selected="" value="">- Todos -</option>
                          <option value="TELEFONO">Telefono</option>
                          <option value="CORREO">Correo electrónico</option>
                          <option value="OTRO">Otro</option>
                          <option value="-1">No contesta</option>
                        </select>
                      </div>
                  </div>
                  <div class="row">
                    <center>
                      <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                    </center>
                  </div>
                  </div>
                </div>
              </div>
          </div>
      </div>


      <div class="row">
        <div>
          <div class="panel panel-default" style="width: 100%;">
            <div class="panel-heading">
              <h4>LISTADO DE AE</h4>
            </div>

            <div class="panel-body">
              <table id="tablaInformesAE" class="table table-fixed tablesorter">
                <thead>
                  <tr>
                    <th style="width: 5%;" value="ae_estado.id_casino" estado="">CAS<i class="fa fa-sort"></i></th>
                    <th style="width: 13%;" value="ae_datos.nro_dni" estado="">DNI<i class="fa fa-sort"></i></th>
                    <th style="width: 14%;" value="estado" estado="">ESTADO<i class="fa fa-sort"></i></th>
                    <th style="width: 9%;" value="ae_datos.apellido" estado="">APELLIDO<i class="fa fa-sort"></i></th>
                    <th style="width: 9%;" value="ae_datos.nombres" estado="">NOMBRES<i class="fa fa-sort"></i></th>
                    <th style="width: 9%;" value="ae_datos.nombre_localidad" estado="">LOCALIDAD<i class="fa fa-sort"></i></th>
                    <th style="width: 9%;" value="ae_datos.nombre_provincia" estado="">PROVINCIA<i class="fa fa-sort"></i></th>
                    <th style="width: 8%;" value="ae_estado.fecha_ae" estado="">F. AE<i class="fa fa-sort"></i></th>
                    <th style="width: 8%;" value="ae_estado.fecha_vencimiento" estado="">F. VENC<i class="fa fa-sort"></i></th> 
                    <th style="width: 8%;" value="ae_estado.fecha_revocacion_ae" estado="">F. REVOC<i class="fa fa-sort"></i></th>
                    <th style="width: 8%;" value="ae_estado.fecha_cierre_ae" estado="">F. CIERRE<i class="fa fa-sort"></i></th>
                  </tr>
                </thead>
                <tbody id="cuerpoTabla" style="height: 350px;">
                  <tr class="filaTabla" style="display: none">
                    <td style="width: 5%;" class="casino"></td>
                    <td style="width: 13%;" class="dni" >
                      <a target="_blank" class="link" title="VER AUTOEXCLUSIÓN">9999</a>
                      <a target="_blank" class="btnVerFoto btn btn-info planilla" type="button" data-toggle="tooltip" data-placement="top" title="VER FOTO" data-delay="{'show':'300', 'hide':'100'}">
                        <i class="far  fa-fw fa-images"></i></a>
                      <span></span>
                    </td>
                    <td style="width: 14%;" class="estado"></td>
                    <td style="width: 9%;" class="apellido"></td>
                    <td style="width: 9%;" class="nombres"></td>
                    <td style="width: 9%;" class="localidad"></td>
                    <td style="width: 9%;" class="provincia" ></td>
                    <td style="width: 8%;" class="fecha_ae" ></td>
                    <td style="width: 8%;" class="fecha_vencimiento_primer_periodo" ></td>
                    <td style="width: 8%;" class="fecha_finalizacion" ></td>
                    <td style="width: 8%;" class="fecha_cierre_ae" ></td>
                    </td>
                  </tr>
                </tbody>
              </table>
              <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
            </div>
          </div>
        </div>
      </div>  <!-- row tabla -->

      @if($usuario->es_superusuario || $usuario->es_administrador || $usuario->es_despacho)
      <div class="row">
        <div class="panel panel-default" style="width: 100%;">
        <div class="panel-heading">
          <h4>EXPORTAR</h4>
          <button type="button" class="btn btn-light" id="agregarCSV">Agregar</button>
          <button type="button" class="btn btn-light" id="limpiarCSV">Limpiar</button>
          <input type="checkbox" class="form-check-input" id="columnasCSV" checked>
          <span>Borrar columnas innecesarias</span>
          <a type="button" class="btn btn-light" id="descargarCSV">Descargar</a>
        </div>
        <div class="panel-body" style="height: 400px;overflow-y: auto;overflow-x: auto;">
        <table id="tablaCSV" class="table table-responsive table-bordered">
          <thead>
            <tr>
              <th class="smalltext casino" style="width: 4%;" data-busq="#buscadorCasino" data-busq-attr='data-codigo'>CAS</th>
              <th class="smalltext estado" style="width: 7%;" data-busq="#buscadorEstado">Estado</th>
              <th class="smalltext apellido" style="width: 7%;" data-busq="#buscadorApellido">Apellido</th>
              <th class="smalltext dia_semanal" style="width: 4%" data-busq="#buscadorDia">Día</th>
              <th class="smalltext rango_etario" style="width: 4%" data-busq="#buscadorRangoEtario" rango>Rango Etario</th>
              <th class="smalltext dni" style="width: 7%;" data-busq="#buscadorDni">DNI</th>
              <th class="smalltext sexo" style="width: 5%;" data-busq="#buscadorSexo">Sexo</th>
              <th class="smalltext localidad" style="width: 8%;" data-busq="#buscadorLocalidad">Localidad</th>
              <th class="smalltext provincia" style="width: 8%;" data-busq="#buscadorProvincia">Provincia</th>
              <th class="smalltext f_ae" style="width: 10%;" data-busq="#dtpFechaAutoexclusion" fecha>Fecha AE</th>
              <th class="smalltext f_v" style="width: 10%;" data-busq="#dtpFechaVencimiento"   fecha>Fecha Venc.</th> 
              <th class="smalltext f_r" style="width: 10%;" data-busq="#dtpFechaRevocacion"    fecha>Fecha Revoc.</th>
              <th class="smalltext f_c" style="width: 10%;" data-busq="#dtpFechaCierre"        fecha>Fecha Cierre</th>
              <th class="smalltext hace_encuesta" style="width: 3%;" data-busq="#buscadorEncuesta" >Encuesta</th>
              <th class="smalltext frecuencia" style="width: 3%;" data-busq="#buscadorFrecuencia" >Frecuencia</th>
              <th class="smalltext veces" style="width: 3%;" data-busq="#buscadorVeces" rango opcional>Veces</th>
              <th class="smalltext horas" style="width: 3%;" data-busq="#buscadorHoras" rango opcional>Horas</th>
              <th class="smalltext compania" style="width: 3%;" data-busq="#buscadorCompania" >Compañia</th>
              <th class="smalltext juego" style="width: 3%;" data-busq="#buscadorJuego" >Juego</th>
              <th class="smalltext programa" style="width: 3%;" data-busq="#buscadorJuegoResponsable" >Programa J.R.</th>
              <th class="smalltext socio" style="width: 3%;" data-busq="#buscadorClub" >Socio</th>
              <th class="smalltext autocontrol" style="width: 3%;" data-busq="#buscadorAutocontrol" >Autocontrol</th>
              <th class="smalltext recibir_info" style="width: 3%;" data-busq="#buscadorRecibirInfo" >Recib. Info</th>
              <th class="smalltext medio" style="width: 3%;" data-busq="#buscadorMedio" >Medio</th>
              <th class="smalltext cant" style="width: 6%;">CANT.</th>
            </tr>
          </thead>
          <tbody>
            <tr class="filaTablaCSV" style="display: none">
              <td class="smalltext casino"    style="width: 4%;">CAS</td>
              <td class="smalltext estado"    style="width: 7%;">ESTADO</td>
              <td class="smalltext apellido"  style="width: 7%;">APELLIDO</td>
              <td class="smalltext dia_semanal" style="width: 4%">DIA</td>
              <td class="smalltext rango_etario" style="width: 4%">00-99</td>
              <td class="smalltext dni"       style="width: 7%;">DNI</td>
              <td class="smalltext sexo"      style="width: 5%;">S</td>
              <td class="smalltext localidad" style="width: 8%;">LOC</td>
              <td class="smalltext provincia" style="width: 8%;">PROV</td>
              <td class="smalltext f_ae"    style="width: 10%;">Fecha AE</td>
              <td class="smalltext f_v"     style="width: 10%;">Fecha Venc.</td> 
              <td class="smalltext f_r"     style="width: 10%;">Fecha Revoc.</td>
              <td class="smalltext f_c"     style="width: 10%;" >Fecha Cierre</td>
              <td class="smalltext hace_encuesta"     style="width: 3%;">Encuesta</td>
              <td class="smalltext frecuencia"   style="width: 3%;">Frecuencia</td>
              <td class="smalltext veces"        style="width: 3%;">Veces</td>
              <td class="smalltext horas"        style="width: 3%;">Horas</td>
              <td class="smalltext compania"     style="width: 3%;">Compañia</td>
              <td class="smalltext juego"        style="width: 3%;">Juego</td>
              <td class="smalltext programa"     style="width: 3%;">Programa J.R.</td>
              <td class="smalltext socio"        style="width: 3%;" >Socio</td>
              <td class="smalltext autocontrol"  style="width: 3%;">Autocontrol</td>
              <td class="smalltext recibir_info" style="width: 3%;">Recib. Info</td>
              <td class="smalltext medio"        style="width: 3%;">Medio</td>
              <td class="smalltext cant"      style="width: 6%;" >CANT.</td>
            </tr>
          </tbody>
        </table>
        </div>
        <div class="panel-footer" style="background: white;">
          <button type="button" class="btn btn-light" id="importarCSV">Importar Busqueda</button>
          <input type="file" id="importarCSVinput" style="display: none;" accept=".csv">
        </div>
        </div>
      </div>  <!-- row tabla -->
      @endif
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
  <script src="js/paginacion.js" charset="utf-8"></script>
  <!-- JavaScript personalizado -->
  <script src="/js/Autoexclusion/informesAE.js" charset="utf-8"></script>
  <!-- Custom input Bootstrap -->
  <script src="/js/fileinput.min.js" type="text/javascript"></script>
  <script src="/js/locales/es.js" type="text/javascript"></script>
  <script src="/themes/explorer/theme.js" type="text/javascript"></script>
  <!-- DateTimePicker JavaScript -->
  <script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
  <script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>
  @endsection
