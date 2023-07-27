<style>
  .validarApertura .borde_abajo {
    border-bottom: 2px solid #ccc;
  }
  .validarApertura .observacion {
    background-color: transparent;
    border: 1px solid #000000;
    height: 100%;
    width: 100%;
    scrollbar-arrow-color: #000066;
    scrollbar-base-color: #000033;
    scrollbar-dark-shadow-color: #336699;
    scrollbar-track-color: #666633;
    scrollbar-face-color: #cc9933;
    scrollbar-shadow-color: #DDDDDD;
    scrollbar-highlight-color: #CCCCCC;
    resize: vertical;
  }
  .validarApertura .tablaFichas th{
    padding-bottom: 8px;
    padding-top: 8px;
    padding-left: 8px;
    padding-right:8px;
    border-right: 1px solid #ccc;
    border-bottom: 1px solid #ccc;
  }
  .validarApertura .tablaFichas th:last_child{
    color: #aaa !important;
    border-right: unset;
  }
  .validarApertura .tablaFichas th h5 {
    font-size: 15px !important;
    color: #aaa !important;
    text-align: center !important;
  }
  .validarApertura .datosA h6,.validarApertura .datosC h6 {
    font-size:17px !important;
    text-align:left !important;
    margin-left:15px;
  }
</style>

<div class="modal fade validarApertura" data-js-validar-apertura tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" style="width:70%">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target=".validarApertura .collapse" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">VALIDAR APERTURA </h3>
      </div>
      <div class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row borde_abajo" style="padding-bottom:20px">
            <div class="col-xs-5">
              <h6 display="inline-block" style="font-size:19px !important; padding:0px;margin:0px !important;">Seleccione un Cierre para validar esta Apertura:</h6>
            </div>
            <div class="col-xs-4" >
              <select name="id_cierre_mesa" class="form-control" data-js-validar-apertura-cambio-fecha display="inline-block" style="padding-right:40px;margin:0px !important;padding-left:0px;">
                <option value="" selected>- Seleccione una Fecha -</option>
              </select>
              <select hidden>
                <option data-js-validar-apertura-molde-fecha value="-1"><span class="fecha">YYYY-MM-DD</span> -- <span class="hora_inicio_format">HH:MM</span> a <span class="hora_fin_format">HH:MM</span> -- <span class="siglas">MONEDA</span></option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-6 borde_abajo datosA" style="border-right: 2px solid #aaa">
              <div class="row">
                <h5>APERTURA</h5>
              </div>
              <div class="row" style="background-color:#BDBDBD;">
                <div class="col-md-12">
                  <h6>HORA APERTURA: <span class="hora"></span></h6>
                  <h6>FECHA APERTURA: <span class="fecha_apertura"></span></h6>
                  <h6>FISCALIZADOR DE TOMA: <span class="fiscalizador"></span></h6>
                  <h6>FISCALIZADOR DE CARGA: <span class="cargador"></span></h6>
                  <h6>TIPO MESA: <span class="tipo_mesa"></span></h6>
                  <h6>MONEDA: <span class="moneda"></span></h6>
                </div>
              </div>
            </div>
            <div class="col-xs-6 borde_abajo datosC" hidden>
              <div class="row">
                <h5>CIERRE</h5>
              </div>
              <div class="row" style="background-color:#BDBDBD;">
                <div class="col-xs-12">
                  <h6>MESA: <span class="nro_mesa"></span></h6>
                  <h6>JUEGO: <span class="juego"></span></h6>
                  <h6>CASINO: <span class="casino"></span></h6>
                  <h6>HORA APERTURA: <span class="hora_inicio"></span></h6>
                  <h6>HORA CIERRE: <span class="hora_fin"></span></h6>
                  <h6>FECHA: <span class="fecha_cierre"></span></h6>
                </div>
              </div>
            </div>
          </div>
          <br>
          <div class="row borde_abajo" style="text-align:center;">
            <h3 align="center" style="padding-bottom:20px; display:inline;position:relative;top:-2px;">DATOS GENERALES</h3><i class="fas fa-info-circle" style="font-size:30px;"></i>
            <br>
            <br>
          </div>
          <div class="row borde_abajo">
            <h6 align="center">FICHAS</h6>
            <table style="border-collapse: separate;" class="table table-bordered tablaFichas">
              <thead>
                <tr>
                  <th class="col-xs-3">
                    <h5>VALOR</h5>
                  </th>
                  <th class="col-xs-3">
                    <h5>CANTIDAD CIERRE</h5>
                  </th>
                  <th class="col-xs-3">
                    <h5>CANTIDAD APERTURA</h5>
                  </th>
                  <th class="col-xs-3">
                    <h5>DIFERENCIAS</h5>
                  </th>
                </tr>
              </thead>
              <tbody style="border-spacing: 7px 7px;">
              </tbody>
            </table>
            <table hidden>
              <tr data-js-validar-apertura-molde-ficha style="padding:0px !important;">
                <td class="valor_ficha" style="padding:1px !important;text-align:right !important;"></td>
                <td class="cierre_cantidad_ficha" style="padding:1px !important;text-align:right !important;font-weight: bold"></td>
                <td class="apertura_cantidad_ficha" style="padding:1px !important;text-align:right !important;"></td>
                <td class="diferencia" style="padding:1px !important;text-align:right !important;">
                  <i data-diferencia="0" class="fa fa-fw fa-check" style="color: rgb(102, 187, 106);" hidden></i>
                  <i data-diferencia="1" class="fa fa-fw fa-times" style="color: rgb(211, 47, 47);" hidden></i>
                </td>
              </tr>
            </table>
          </div>
          <div class="row">
            <div class="col-md-4">
              <h6>TOTAL CIERRE</h6>
              <input type="text" class="form-control total_pesos_fichas_c" readonly="true">
            </div>
            <div class="col-md-4" >
              <h6>TOTAL APERTURA</h6>
              <input type="text" class="form-control total_pesos_fichas_a" readonly="true">
            </div>
            <div class="col-md-4" >
              <h6>TOTAL ANTICIPOS</h6>
              <input type="text" class="form-control total_anticipos_c" readonly="true">
            </div>
          </div>
          <br>
          <div class="row">
            <div class="col-md-12">
              <h6>OBSERVACIONES</h6>
              <textarea name="observacion" class="observacion"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button data-js-validar-apertura-validar data-diferencia="0" type="button" class="btn btn-successAceptar" hidden>VALIDAR</button>
            <button data-js-validar-apertura-validar data-diferencia="1" type="button" class="btn btn-successAceptar" hidden>VALIDAR CON DIFERENCIA</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
