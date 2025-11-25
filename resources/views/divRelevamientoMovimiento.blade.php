<?php
use App\Http\Controllers\UsuarioController;
use App\DetalleRelevamientoProgresivo;
$divRelMov_ucontrol = UsuarioController::getInstancia();
$divRelMov_user = $divRelMov_ucontrol->quienSoy()['usuario'];
$maxlvl = (new DetalleRelevamientoProgresivo)->max_lvl;
?>

<div id="divRelMov">
<div class="row">
    <div class="col-md-3">
        <h5 class="row">Tipo Movimiento</h5>
        <input class="row form-control tipoMov" type="text" autocomplete="off" readonly="">
    </div>
    <div class="col-md-3">
        <h5 class="row">Sentido</h5>
        <input class="row form-control sentidoMov" type="text" autocomplete="off" readonly="" placeholder="Reingreso - Egreso temporal">
    </div>
    <div class="col-md-4">
        <h5 class="row">Expediente</h5>
        <div class="row">
            <div class="col-md-3"><input class="form-control exp_org" type="text" autocomplete="off" disabled="disabled" placeholder="xxxxx" maxlength=5></div>
            <div class="col-md-4"><input class="form-control exp_interno" type="text" autocomplete="off" disabled="disabled" placeholder="xxxxxxx" maxlength=7></div>
            <div class="col-md-3"><input class="form-control exp_control" type="text" autocomplete="off" disabled="disabled" placeholder="x" maxlength=1></div>
        </div>
    </div>
    <div class="col-md-2">
        <h5 class="row">Disposicion</h5>
        <div class="row">
            <div class="col-md-7"><input class="form-control disposicion" type="text" autocomplete="off" disabled="disabled" placeholder="xxx" maxlength=3></div>
            <div class="col-md-5"><input class="form-control disposicion_anio" type="text" autocomplete="off" disabled="disabled" placeholder="xx" maxlength=2></div>
        </div>
    </div>
</div>
<div class="row"> <!-- row inicial -->
    <div class="col-md-3" style="overflow-y: scroll; height: 950px;">
        <h5>Máquinas</h5>
        <table class="table tablaMTM">
        <thead>
            <tr>
            <th> </th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        </table>
    </div> <!-- maquinas -->
    <div class="col-md-9 detalleRel">
        <div class="row">
            <div class="col-lg-3">
                <h5>Estado</h5>
                <input type="text" class="form-control estado" readonly="readonly">
            </div>
            <div class="col-md-3">
                <h5>Fiscalizador Carga: </h5>
                <input type="text" class="form-control fiscaCarga" disabled="true">
            </div>
            <div class="col-md-3">
                <h5>Fiscalizador Toma: </h5>
                <input class="form-control editable fiscaToma" type="text" autocomplete="off">
            </div>
            <div class="col-md-3">
                <h5>Fecha Ejecución: </h5>
                <div class='input-group date relFecha' data-date-format="yyyy-mm-dd HH:ii:ss">
                    <input type='text' class="form-control editable fechaRel" placeholder="Fecha de ejecución del relevamiento" data-trigger="manual" data-toggle="popover" data-placement="top" />
                    <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                    <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
            </div>
        </div>
        <h6>DETALLES MTM</h6>
        <form class="" action="index.html" method="post">
            <div class="row" style="display: flex;">
                <div style="flex: 1;">
                    <h5>Nro Admin.</h5>
                    <input type="text" class="form-control nro_admin" readonly="readonly">
                </div>
                <div style="flex: 1;">
                    <h5>N° Isla (Sistema)</h5>
                    <input type="text" class="form-control nro_isla" readonly="readonly">
                </div>
                @if($divRelMov_user->tienePermiso('eventualidades_validar_borrar'))
                <div style="flex: 1;">
                    <h5>N° Isla (Importado)</h5>
                    <input type="text" class="form-control nro_isla_importado" readonly="readonly">
                </div>
                @endif
                <div style="flex: 1;">
                    <h5>N° Serie</h5>
                    <input type="text" class="form-control nro_serie" readonly="readonly">
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6">
                    <h5>Marca</h5>
                    <input type="text" class="form-control marca" readonly="readonly">
                </div>
                <div class="col-lg-6">
                    <h5>Modelo</h5>
                    <input id="" type="text" class="form-control modelo" readonly="readonly">
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4">
                    <h5>MAC</h5>
                    <input type="text" class="form-control editable mac">
                </div>
                <div class="col-lg-4">
                    <h5>SECTOR</h5>
                    <input type="text" class="form-control editable sector_rel">
                </div>
                <div class="col-lg-4">
                    <h5>N° Isla (Relevado)</h5>
                    <input type="text" class="form-control editable isla_rel">
                </div>
            </div>
            <div class="row">
                <div class="table-editable tablaCont">
                    <table id="" class="table">
                        <thead>
                            <tr>
                                <th class="col-xs-6"><h6><b>CONTADORES</b></h6></th>
                                <th class="col-xs-6"><h6><b>TOMA</b></h6></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            <h6>TOMA</h6>
            <div class="row">
                <div class="col-lg-4">
                    <h5>JUEGO</h5>
                    <select class="form-control editable juego">
                        <option value=""></option>
                    </select>
                </div>
                <div class="col-lg-4">
                    <h5>APUESTA MÁX</h5>
                    <input type="text" class="form-control editable apuesta">
                </div>
                <div class="col-lg-4">
                    <h5>CANT LÍNEAS</h5>
                    <input type="text" class="form-control editable cant_lineas">
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4">
                    <h5>% DEVOLUCIÓN</h5>
                    <input type="text" class="form-control editable devolucion">
                </div>
                <div class="col-lg-4">
                    <h5>DENOMINACIÓN</h5>
                    <input type="text" class="form-control editable denominacion">
                </div>
                <div class="col-lg-4">
                    <h5>CANT CRÉDITOS</h5>
                    <input type="text" class="form-control editable creditos">
                </div>
            </div>
            <h6>PROGRESIVOS</h6>
            <div class="row">
                <div class="col-lg-12" style="overflow: scroll;max-height: 250px;">
                    <h5 class="sinProg" hidden>La toma no posee progresivos asignados</h5>
                    <table class="table table-fixed tablaProg">
                        <thead>
                            <tr>
                                <th width="17%">PROGRESIVO</th>
                                @for($i=1;$i<=$maxlvl;$i++)
                                <th width="11%">NIVEL{{$i}}</th>
                                @endfor
                                <th width="17%">CAUSA NO TOMA</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            <table hidden>
                <tr class="filaEjProg">
                    <td class="nombreProgresivo" width="17%">PROGRESIVO99</td>
                    @for ($i=1;$i<=$maxlvl;$i++)
                    <td width="11%">
                        <input class="nivel{{$i}} form-control editable" min="0" data-toggle="tooltip" data-placement="down" title="nivel{{$i}}">
                    </td>
                    @endfor
                    <td width="17%">
                    <select class="causaNoToma form-control editable">
                        <option value=""></option>
                        @foreach($causasNoTomaProgresivo as $causa)
                        <option value="{{$causa->id_tipo_causa_no_toma_progresivo}}">{{$causa->descripcion}}</option>
                        @endforeach
                    </select>
                    </td>
                </tr>
            </table>
            <table hidden>
                <tr class="filaEjCont">
                    <td class="col-xs-6 cont" data-contador=""></td>
                    <td class="col-xs-6">
                        <input class="form-control editable vcont valorModif">
                    </td>
                </tr>
            </table>
            <h6>OBSERVACIONES</h6>
            <div class="row">
                <div class="col-lg-12">
                    <textarea id="" class="form-control editable observaciones" style="resize:vertical;"></textarea>
                </div>
            </div>
            <h6>ADJUNTO</h6>
            <div class="row">
              <div class="col-md-12" style="display: flex;">
                <input type="file" class="form-control editable adjunto" style="width: 20em;" accept="image/*" capture="environment">
                <span>&nbsp;</span>
                <img class="imagen_adjunto" style="width: 15em;border: 1px solid #ddd">
                <button type="button" class="btn btn-link eliminar_adjunto" style="color: red;">X</button>
              </div>
            </div>
            <div class="validacion">
            @if($divRelMov_user->es_controlador)
                <h6>OBSERVACIONES ADMIN:</h6>
                <div class="row">
                    <div class="col-lg-12">
                        <textarea id="" class="form-control observacionesAdm"  maxlength="200" style="resize:vertical;"></textarea>
                    </div>
                </div>
                <hr>
                <!-- 1 era muy chico y 2 muy grande, tuve que hacer 1.5 asi -->
                <!-- |   PAD (6)  | COL(3) | COL (3) | -->
                <!-- |PAD         |PAD |ERR|VAL|  PAD| -->
                <div class="row">
                    <div class="col-lg-3 col-lg-offset-6">
                        <div class="row">
                            <div class="col-lg-6 col-lg-offset-6">
                                <button type="button" class="btn btn-danger error"><b>ERROR</b></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="row">
                            <div class="col-lg-6">
                                <button type="button" class="btn btn-success validar"><b>VALIDAR</b></button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            </div>
        </form>
    </div> <!-- fin detalle -->
</div>
</div>
<div class="modal fade" id="modalDivRelCambios" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-titleEliminar">ADVERTENCIA</h3>
                </div>

                <div class="modal-body franjaRojaModal">
                  <form id="frmEliminar" name="frmCasino" class="form-horizontal" novalidate="">
                      <div class="form-group error ">
                        <div class="col-xs-12">
                            <strong id="titulo-modal-eliminar">Diferencias detectadas desde su egreso</strong>

                            <ul id="listaCambios" class="list-unstyled"></ul>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                  <button type="button" class="btn btn-caution" id="btnConfirmarCambios">Guardar de todos modos</button>
                </div>
            </div>
          </div>
</div>

<!-- Lo incluyo porque el script lo usa -->
<script src="js/utils.js" type="text/javascript"></script>
<script>
const divRelMovMaxLVLProg = {{$maxlvl}};
</script>
