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
              <br>
              <h2><span>RMES02 | Informe Diario, Mesas de Paño.</span></h2>
        </div>
              <div class="camposTab titulo" style="right:-25px;">FECHA INFORME</div>
              <div class="camposInfo" style="right:0px;"></span>{{$rel->informe->fecha}}</div>

            <table class="tablaInicio"  style="border-collapse: collapse;" width= '90%'>
              <thead>
                <th style="font-size:15px; text-align:center !important">
                  DATOS
                </th>
              </thead>

              <tbody>
                <tr style="border-bottom:0px solid #ccc; font-size:15px; width:100% !important; color:white;">
                  FGHFH
                </tr>
                <tr style="border-bottom:1px solid #ccc">
                  <td><i>Cantidad de Cierres cargados: {{$rel->informe->cant_cierres}}</i></td>
                </tr>
                <tr style="border-bottom:1px solid #ccc">
                  <td><i>Cantidad de Aperturas relevadas: {{$rel->informe->cant_aperturas}}</i></td>
                </tr>
                <tr style="border-bottom:1px solid #ccc">
                  <td><i>Cantidad de Mesas Abiertas: {{$rel->informe->cant_mesas_abiertas}}</i></td>
                </tr>
                <tr style="border-bottom:1px solid #ccc">
                  <td><i>Cantidad de Mesas Totales: {{$rel->informe->cant_mesas_totales}}</i></td>
                </tr>
                <tr style="border-bottom:1px solid #ccc">
                  <td><i>Cantidad de Mesas con Diferencias: {{$rel->informe->cant_mesas_con_diferencias}}</i></td>
                </tr>
                <tr style="border-bottom:1px solid #ccc">
                  <td><i>Cantidad de Mesas con el Valor Mínimo: {{$rel->informe->cantidad_abiertas_con_minimo}}</i></td>
                </tr>
                @if($rel->informe->cumplio_minimo == '1')
                <tr style="border-bottom:1px solid #ccc;">
                  <td><i>No se ha cumplido con el mínimo requerido de apuestas en los turnos: {{$rel->turnos_no_cumplen}}</i></td>
                </tr>
                @else
                <tr style="border-bottom:1px solid #ccc;">
                  <td><i>Se ha cumplido con el mínimo requerido de apuestas</i></td>
                </tr>
                @endif
                <tr style="border-bottom:1px solid #ccc;">
                  @if($rel->relevamientos_incorrectos == '')
                  <td><i>Existe <b>diferencias</b> entre los datos cargados en los Relevamientos de Valores de Apuestas y los datos importados para el día de la fecha</i></td>
                  @else
                  <td> <i>Los datos de Relevamientos de Valores de Apuestas <b>coinciden</b> con los datos importados para el día de la fecha</i> </td>
                  @endif
                </tr>
              </tbody>
            </table>
            <br>
            <br>

      <h5 style="font-family:Roboto-Regular !important; font-size:15px;text-align:center !important">TABLA DE DIFERENCIAS CIERRES Y APERTURAS</h5>
      @if($rel->mesas_con_diferencia != null)
      <table style="border-collapse: collapse;">
        <thead>
          <tr align="center">
            <th class="col-xl-2 tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important">Mesa</th>
            <th class="col-xl-3 tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important">Diferencias</th>
            <th class="col-xl-2 tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray; text-align:center !important;">Observaciones</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rel->mesas_con_diferencia as $diff)
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
