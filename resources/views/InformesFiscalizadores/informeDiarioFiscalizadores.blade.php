<!DOCTYPE html>

<html>

<style>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 98%;
}

td, th {
  border: 1px solid #dddddd;
  text-align: left;
  padding: 3px;
}

tr:nth-child(even) {
  background-color: #dddddd;
}

p {
      border-top: 1px solid #000;
}

footer
{
    margin-top:50px;
    width:200%;
    height:300px;
}
</style>

  <head>
    <meta charset="utf-8">
    <title></title>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
    <script src="/js/jquery.js"></script>
    <script src="/js/bootstrap.js"></script>
    <script src="/js/ajaxError.js"></script>

  </head>
  <body>

        <div class="encabezadoImg">
              <img style="margin-top:-6px !important" src="img/logos/banner_loteria_landscape2_f.png" width="900">
              <h2><span>PMES01 | Informe Diario - Mesas de Paño</span></h2>
        </div>
              <div class="camposTab titulo" style="right:-25px;">FECHA INFORME</div>
              <div class="camposInfo" style="right:0px;"></span>{{$rel->informe->fecha}}</div>

              @if(count($rel->minimos) > 0)
              <h4>VALORES MÍNIMOS REQUERIDOS PARA {{strtoupper($rel->informe->casino->nombre)}}</h4>
              <table style="border-collapse: collapse;" >
                <thead>
                  <tr align="center" >
                    <th class="tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important">MONEDA</th>
                    <th class="tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray; text-align:center !important;">JUEGO</th>
                    <th class="tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray; text-align:center !important;">VALOR MÍNIMO</th>
                    <th class="tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray; text-align:center !important;">CANT. MÍN. REQ.</th>
                    <th class="tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray; text-align:center !important;">CANT. CUMPLIERON</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($rel->minimos as $min)
                    <tr>
                      <td class="tablaCampos" style="text-align:center !important; font-size:13px !important">{{$min->moneda}}</td>
                      <td class="tablaCampos" style="text-align:center !important; font-size:13px !important">{{$min->nombre_juego}}</td>
                      <td class="tablaCampos" style="text-align:center !important; font-size:13px !important">{{$min->valor_minimo}}</td>
                      <td class="tablaCampos" style="text-align:center !important; font-size:13px !important">{{$min->cantidad}} </td>
                      <td class="tablaCampos" style="text-align:center !important; font-size:13px !important">{{$min->cantidad_cumplieron}} </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
              <br>
              <br>
            @endif
            <table class="tablaInicio"  style="border-collapse: collapse;">
              <thead>
                <th style="font-size:15px; text-align:center !important">
                   {{$rel->informe->fecha}} - {{strtoupper($rel->informe->casino->nombre)}}
                </th>
              </thead>
              <tbody>
                <tr style="border-bottom:1px solid #ccc; font-size:15px;">
                  
                </tr>
                <tr style="border-bottom:1px solid #ccc">
                  <td><i>Cantidad de Cierres cargados: {{$rel->informe->cant_cierres}}</i></td>
                </tr>
                <tr style="border-bottom:1px solid #ccc">
                  <td><i>Cantidad de Cierres sin validar: {{$rel->informe->cie_sin_validar}}</i></td>
                </tr>
                <tr style="border-bottom:1px solid #ccc">
                  <td><i>Cantidad de Aperturas relevadas: {{$rel->informe->cant_aperturas}}</i></td>
                </tr>
                <tr style="border-bottom:1px solid #ccc">
                  <td><i>Cantidad de Aperturas sin validar: {{$rel->informe->ap_sin_validar}}</i></td>
                </tr>
                <tr style="border-bottom:1px solid #ccc">
                  <td><i>Porcentaje de Aperturas relevadas sobre sorteadas: {{$rel->informe->aperturas_sorteadas}}</i></td>
                </tr>
                <tr style="border-bottom:1px solid #ccc">
                  <td><i>Cantidad de Mesas Abiertas: {{$rel->informe->cant_mesas_abiertas}}</i></td>
                </tr>
                <tr style="border-bottom:1px solid #ccc">
                  <td><i>Cantidad de Mesas Totales: {{$rel->informe->cant_mesas_totales}}</i></td>
                </tr>
                <tr style="border-bottom:1px solid #ccc">
                  <td><i>Cantidad de Mesas con Diferencias:
                    @if($rel->informe->cant_mesas_con_diferencias == 0)
                    0
                    @else
                    {{$rel->informe->cant_mesas_con_diferencias}}
                    @endif
                  </i></td>
                </tr>

                @if($rel->informe->cumplio_minimo != '1')
                <tr style="border-bottom:1px solid #ccc;">
                  <td><i>No se ha cumplido con el mínimo requerido de apuestas en los turnos: {{$rel->informe->turnos_sin_minimo}}</i></td>
                </tr>
                @else
                <tr style="border-bottom:1px solid #ccc;">
                  <td><i>Se ha cumplido con el mínimo requerido de apuestas</i></td>
                </tr>
                @endif
                <tr style="border-bottom:1px solid #ccc;">
                  @if($rel->relevamientos_incorrectos == 'true')
                  <td><i>Existen <b>diferencias</b> entre los datos cargados en los Relevamientos de Valores de Apuestas y los datos importados para el día de la fecha</i></td>
                  @else
                  <td> <i>Los datos de Relevamientos de Valores de Apuestas <b>coinciden</b> con los datos importados para el día de la fecha</i> </td>
                  @endif
                </tr>
              </tbody>
            </table>
            <br>
            <br>
      @if($rel->informe->mesas_con_diferencia != 'null' && $rel->informe->mesas_con_diferencia != '{}')
        <h5 style="font-family:Roboto-Regular !important; font-size:15px;text-align:center !important">TABLA DE DIFERENCIAS CIERRES Y APERTURAS</h5>

        <table style="border-collapse: collapse;">
          <thead>
            <tr align="center">
              <th class="col-xl-2 tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important">Mesa</th>
              <th class="col-xl-3 tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important">Diferencias</th>
              <th class="col-xl-2 tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray; text-align:center !important;">Observaciones</th>
            </tr>
          </thead>
          <tbody>
            <?php $mesas_con_dif = json_decode($rel->informe->mesas_con_diferencia,true); ?>
            @foreach($mesas_con_dif as $diff)
              <tr>
                <th class="col-xl-2 " style=" font-size:12px; border-color: gray;text-align:center !important;">
                  {{$diff['mesa']}}
                </th>
                <th class="col-xl-3 " style="font-size:12px; border-color: gray;text-align:center !important;">
                  {{$diff['diferencia']}}
                </th>
                <th class="col-xl-2 " style="font-size:12px; border-color: gray;text-align:center !important;">
                  {{$diff['observacion']}}
                </th>
              </tr>
            @endforeach
          </tbody>
        </table>
        <br>
        <br>
      @endif
  </body>
</html>

@section('scripts')

  <!-- JavaScript personalizado -->


@endsection
