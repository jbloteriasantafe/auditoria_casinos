
<!-- Modal validar relevamientos -->
<div class="modal fade" id="modalValidarRelevamiento" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width:94%;">
    <div class="modal-content">
      <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#69F0AE;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizarValidar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoValidar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| DETALLES RELEVAMIENTO</h3>
      </div>
      <div id="colapsadoValidar" class="collapse in">
        <div class="modal-body modalCuerpo">
          <form id="frmValidarRelevamiento" name="frmValidarRelevamiento" class="form-horizontal" novalidate="">
            <div class="row">
              <div class="col-lg-2">
                <h5>FECHA</h5>
                <input id="validarFechaActual" type='text' class="form-control" readonly>
                <br>
              </div>
              <div class="col-lg-2">
                <h5>CASINO</h5>
                <input id="validarCasino" type='text' class="form-control" readonly>
                <br>
                <span id="alertaCasino" class="alertaSpan"></span>
              </div>
              <div class="col-lg-2">
                <h5>SECTOR</h5>
                <input id="validarSector" type='text' class="form-control" readonly>
                <br>
                <span id="alertaSector" class="alertaSpan"></span>
              </div>
              <div class="col-md-2">
                <h5>FISCALIZADOR CARGA</h5>
                <input id="validarFiscaCarga" type="text"class="form-control" readonly>
              </div>
              <div class="col-md-2">
                <h5>FISCALIZADOR TOMA</h5>
                <input id="validarFiscaToma" type="text"class="form-control" readonly>
              </div>
              <div class="col-md-2">
                <h5>TÉCNICO</h5>
                <input id="validarTecnico" type="text"class="form-control" readonly>
              </div>
            </div>
            <br>
            <div class="row">
              <div class="col-md-12">
                <table id="tablaValidarRelevamiento" class="table">
                  <thead>
                    <tr>
                      <th width="3%">MÁQ</th>
                      @for($c=1;$c<=$CONTADORES;$c++)
                      <th {{$c<=$CONTADORES_VISIBLES? '' : 'hidden'}}>CONTADOR {{$c}}</th>
                      @endfor
                      <th>P. CALCULADO ($)</th>
                      <th>P. IMPORTADO ($)</th>
                      <th>DIFERENCIA</th>
                      <th>&nbsp;</th>
                      <th>TIPO NO TOMA</th>
                      <th>DEN</th>
                      <th>A PEDIDO</th>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
            </div>
            <br>
            <div class="row">
              <div class="col-md-8 col-md-offset-2">
                <h5>OBSERVACIONES FISCALIZADOR</h5>
                <textarea id="observacion_fisca_validacion" class="form-control" style="resize:vertical;" readonly="true"></textarea>
              </div>
            </div>
            <br>
            <div class="row">
              <div class="col-md-8 col-md-offset-2">
                <h5>OBSERVACIONES DE VISADO</h5>
                <textarea id="observacion_validacion" class="form-control" style="resize:vertical;"></textarea>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-successAceptar" id="btn-finalizarValidacion" value="nuevo">VISAR RELEVAMIENTO</button>
          <button type="button" class="btn btn-default" id="btn-salirValidacion" data-dismiss="modal">SALIR</button>
          <input type="hidden" id="id_relevamiento" value="0">
          <div id="mensajeValidacion" hidden>
            <br>
            <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">NO SE PUEDE VISAR</span>
            <br>
            <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">No se importaron los contadores para dicha fecha.</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<table hidden>
  <tr class="moldeVer">
    <td class="nro_admin" style="text-align: center;">MAQ</td>
    @for($c=1;$c<=$CONTADORES;$c++)
    <td class="cont{{$c}}" {{$c<=$CONTADORES_VISIBLES? '' : 'hidden'}} style="text-align: right;">CONT{{$c}}</td>
    @endfor
    <td class="producido_calculado_relevado" style="text-align: center;">PROD CALC</td>
    <td class="producido_importado" style="text-align: center;">PROD CALC</td>
    <td class="diferencia" style="text-align: center;">DIF</td>
    <td style="text-align: center;">&nbsp;</td>
    <td class="tipo_no_toma" style="text-align: center;">NO TOMA</td>
    <td class="denominacion">DENO</td>
    <td class="fecha">fecha</td>
  </tr>
</table>

