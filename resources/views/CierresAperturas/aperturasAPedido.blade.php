<div class="modal fade aperturaAPedido" data-js-apertura-a-pedido tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" style="width: 80%">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#4AA89F;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true"
                data-target=".aperturaAPedido .collapse" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| APERTURAS A PEDIDO</h3>
      </div>
      <div class="collapse in">
        <div class="modal-body">
          <div class="row">
            <div class="col-xs-2">
              <h5>JUEGO</h5>
              <select class="form-control" data-js-juego>
                @foreach ($juegos as $j)
                <option value="{{$j->id_juego_mesa}}" data-siglas="{{$j->siglas}}" data-casino="{{$j->casino->nombre}}">
                  {{$j->nombre_juego}} - {{$j->casino->codigo}}
                </option>
                @endforeach
              </select>
            </div>
            <div class="col-xs-2">
              <h5>MESA</h5>
              <input class="form-control" data-js-mesa name="id_mesa_de_panio" data-js-formdata-attr="data-elemento-seleccionado" placeholder="Número de mesa"/>
            </div>
            <div class="col-xs-2">
              <h5>F. INICIO</h5>
              <div class="form-group">
                <div class='input-group date' data-js-fecha data-date-format="aaaa-mm-dd">
                  <input type='text' class="form-control" name="fecha_inicio" placeholder="Fecha de inicio"/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
            </div>
            <div class="col-xs-2">
              <h5>F. FIN</h5>
              <div class="form-group">
                <div class='input-group date' data-js-fecha data-date-format="aaaa-mm-dd">
                  <input type='text' class="form-control" name="fecha_fin" placeholder="Fecha fin"/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
            </div>
            <div class="col-xs-2">
              <h5>&nbsp;</h5>
              <button data-js-agregar class="btn btn-success" type="button">
                <i class="fa fa-plus"></i>
              </button>
            </div>
          </div>
          <hr>
          <div class="row" style="max-height: 450px;overflow-y: scroll;">
            <table class="table" data-js-tabla>
              <thead>
                <tr>
                  <th class="col-md-2" style="text-align:center;">CASINO</th>
                  <th class="col-md-2" style="text-align:center;">MONEDA</th>
                  <th class="col-md-2" style="text-align:center;">JUEGO</th>
                  <th class="col-md-2" style="text-align:center;">MESA</th>
                  <th class="col-md-2" style="text-align:center;">FECHA INICIO</th>
                  <th class="col-md-2" style="text-align:center;">FECHA FIN</th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>
            <table hidden>
              <tr data-js-molde>
                <td class="casino" style="text-align:center;">CASINO</td>
                <td class="moneda" style="text-align:center;">ARS/USD/MULTIMONEDA</td>
                <td class="juego" style="text-align:center;">RA/CR/MJ/ETC</td>
                <td class="nro_mesa" style="text-align:center;">1234</td>
                <td class="fecha_inicio" style="text-align:center;">9999-99-99</td>
                <td class="fecha_fin" style="text-align:center;">9999-99-99</td>
                <td style="text-align:center;">
                  <button type="button" class="btn btn-success" data-js-eliminar-aap>
                    <i class="fa fa-fw fa-trash"></i>
                  </button>
                </td>
              </tr>
            </table>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
      </div>
    </div>
  </div>
</div>
