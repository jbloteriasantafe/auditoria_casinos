<div id="modalAsignarIslotes" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="width:90%;">
      <div class="modal-content">
        <div class="modal-header" style="font-family: Roboto-Black; background: #ff9d2d;">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
          <h3 class="modal-title">ASIGNAR ISLOTES</h3>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-3">
              <h5>CASINO</h5>
              <select id="casinoIslotes" class="form-control">
                @foreach ($casinos as $casino)
                <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <h5>Agregar islote</h5>
              <input id="agregarIslote" class="form-control" placeholder="Presione ENTER"/>
            </div>
            <div class="col-md-3">
              <h5>&nbsp;</h5>
              <i class="asignar_borrar_islote fa fa-fw fa-trash-alt" style="font-size: 200%;color: gray;"></i>
            </div>
          </div>
          <div id="escondido_pre_insertar" class="row" hidden></div>
          <div id="sectores" class="row" style="height: 550px;overflow-y: scroll;">
          </div>
        </div>
        <div class="modal-footer">
          <button id="btn-aceptarIslotes" type="button" class="btn btn-success btn-warningModificar">ACEPTAR</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
        </div>
      </div>
    </div>
</div>

<div hidden>
  <style>
    .islotes{
      display: flex;
      flex-direction: row;
      flex-wrap: wrap;
      justify-content: flex-start;
      align-items: stretch;
      align-content: stretch;
    }
    .asignar_islote {
      width: 49%;
      margin: 0.5%;
      min-height: 100px;
      border-top: 1px #ccc solid;
      border-left: 1px #ddd solid;
      border-right: 1px #ccc solid;
      border-bottom: 1px #ddd solid;
      border-radius: 25px;
    }
    .islotes .nro_islote {
      text-align: center;
      text-shadow: 1px 1px 0px white;
      border-radius: 25px 25px 0px 0px;
      background-color: #ccc;
      margin-top: 0px;
    }
    .islas {
      display: flex;
      flex-direction: row;
      flex-wrap: wrap;
      justify-content: center;
      align-items: center;
      align-content: center;
    }
    .asignar_isla {
      text-align: center;
      border: 1px #ddd solid;
      min-width: 8%;
    }
    .asignar_isla:hover,.asignar_islote:hover{
      border: 2px dashed orange; 
    }
    .islotes .nro_islote:hover{
      border-bottom: 2px solid orange; 
    }
    .sombreado {
      background-color: #fef;
      border: 1px solid red;
    }
    .seleccionado {
      border: 2px solid blue !important;
    }
    .movido_reciente{
      animation: sacarcolor 3s;
    }
    @keyframes sacarcolor{
      0%   {background-color: orange;}
      100% {background-color: unset;}
    }
  </style>
  <div id="moldeSector" class="asignar_sector">
    <h3 class="nombre_sector">SECTOR ZZZ</h3>
    <div class="islotes hijos"></div>
  </div>
  <div id="moldeIslote" class="asignar_islote">
    <h4 class="nro_islote">ISLOTE XXX</h4>
    <div class="islas hijos"></div>
  </div>
  <div id="moldeIslaIslote" class="asignar_isla">
    <span class="nro_isla hijos">ISLAYYY</span>
  </div>
</div>