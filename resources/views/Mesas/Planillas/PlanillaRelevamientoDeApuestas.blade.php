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


p {
      border-top: 1px solid #000;
      font-family: arial, sans-serif;
}

footer
{
    margin-top:50px;
    width:200%;
    height:300px;
}
</style>

  <head>

    <title></title>

    <!-- <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> -->

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- <link href="css/bootstrap.min.css" rel="stylesheet"> -->

    <link href="{{ asset('css/estiloPlanillaLandscapeMesas.css') }}" rel="stylesheet">
  </head>
  <body>
    @foreach($rel->paginas as $pagina)
      @if($pagina['count_nro_pagina'] > 1)
        <div style="page-break-after:always;"></div>
      @endif
        <div class="encabezadoImg">
              <img src="{{ asset('img/logos/banner_loteria_landscape2_f.png') }}" width="1090">
              <h2><span>RMES03 | Relevamiento de valores de apuestas de Mesas de Paño.</span></h2>
        </div>
        <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
        <div class="camposInfo" style="right:0px;"></span>{{$rel->fecha}}</div>
        <table style="border-collapse: collapse;  table-layout:fixed; width:100%">
          <tr style="width:100%">
            <th style="font-size: 11px !important; border-color: gray; width:10px !important;" >FECHA EJECUCIÓN</th>
            <td style="font-size: 11px !important; border-color: gray; width:10px !important;" >{{$rel->fecha_backup}}</td>
            <th style="font-size: 11px !important; border-color: gray; width:10px !important;">TURNO</th>
            <td style="font-size: 11px !important; border-color: gray; width:10px !important;">{{$rel->turno}}</td>
            <th style="border-color: gray; font-size: 11px !important; width:10px !important;">HORA PROPUESTA</th>
            <td style="border-color: gray; font-size: 11px !important; width:10px !important;">{{$rel->hora_propuesta}}</td>
            <th style="border-color: gray; font-size: 11px !important; width:10px !important;">HORA EJECUCIÓN</th>
            <td style="border-color: gray; font-size: 11px !important; width:10px !important;">{{$rel->hora_ejecucion}}</td>
          </tr>
        </table>
        <div style="border-collapse: collapse;position:relative" >
          <div style="border-collapse: collapse;float: left; width: 50%;">
              <table style="width:100%;"  class="table table-responsive">

                <tbody>
                  <tr >
                    <th style="border-color: gray;text-align:center !important; font-size:11px !important;height: 30px !important">JUEGO</th>
                    <th style="border-color: gray;text-align:center !important;font-size:11px !important;height: 30px !important">CÓDIGO</th>
                    <th style="border-color: gray;text-align:center !important;font-size:11px !important;height: 30px !important">MONEDA</th>
                    <th style="border-color: gray;text-align:center !important;font-size:11px !important;height: 30px !important">POS.</th>
                    <th style="border-color: gray;text-align:center !important;font-size:11px !important;height: 30px !important">ESTADO (A/C/T)</th>
                    <th style="border-color: gray;text-align:center !important;font-size:11px !important;height: 30px !important">MÍNIMA</th>
                    <th style="border-color: gray;text-align:center !important;font-size:11px !important;height: 30px !important">MÁXIMA</th>
                  </tr>

                  @foreach($pagina['izquierda'] as $myji)
                    <tr>
                      <td  vertical-aling="middle" style="border-width:2px; border-color:#757575;" rowspan="{{$myji['filas']+1}}">{{$myji['juego']}}</td>
                      <td colspan="6" style="background-color: gray; font-size:1px !important;">11</td>
                      @foreach($myji['mesas'] as $detalle)
                      <tr>
                        <td style="border-color: gray;font-size:10px !important;">{{$detalle['codigo_mesa']}}</td>
                        <td style="border-color: gray;font-size:10px !important;">{{$detalle['siglas']}}</td>
                        <td style="border-color: gray;font-size:10px !important;">{{$detalle['posiciones']}}</td>
                        <td style="border-color: gray;font-size:10px !important;">{{$detalle['estado']}}</td>
                        <td style="border-color: gray;font-size:10px !important;">{{$detalle['minimo']}}</td>
                        <td style="border-color: gray;font-size:10px !important;">{{$detalle['maximo']}}</td>
                      </tr>
                      @endforeach
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <div style="float: right; width: 50%;position:absolute;" >
              @if( $pagina['derecha'] != null)
              <table style="width:100%;position:relative" class="table table-responsive" >


                <tbody >
                  <tr style="float:right; ">
                    <th style="border-color: gray;text-align:center !important; font-size:11px !important;height: 30px !important">JUEGO</th>
                    <th style="border-color: gray;text-align:center !important;font-size:11px !important;height: 30px !important">CÓDIGO</th>
                    <th style="border-color: gray;text-align:center !important;font-size:11px !important;height: 30px !important">MONEDA</th>
                    <th style="border-color: gray;text-align:center !important;font-size:11px !important;height: 30px !important">POS.</th>
                    <th style="border-color: gray;text-align:center !important;font-size:11px !important;height: 30px !important">ESTADO (A/C/T)</th>
                    <th style="border-color: gray;text-align:center !important;font-size:11px !important;height: 30px !important">MÍNIMA</th>
                    <th style="border-color: gray;text-align:center !important;font-size:11px !important;height: 30px !important">MÁXIMA</th>
                  </tr>

                  @foreach($pagina['derecha'] as $myjd)
                  <td  style="border:2px solid #757575 !important;" rowspan="{{$myjd['filas']+1}}">{{$myjd['juego']}}</td>

                  <td colspan="6" style="background-color: gray; font-size:1px !important;">11</td>

                    <tr>
                      @foreach($myjd['mesas'] as $detalle)
                      <tr>
                        <td style="border-color: gray;font-size:10px !important;">{{$detalle['codigo_mesa']}}</td>
                        <td style="border-color: gray;font-size:10px !important;">{{$detalle['siglas']}}</td>
                        <td style="border-color: gray;font-size:10px !important;">{{$detalle['posiciones']}}</td>
                        <td style="border-color: gray;font-size:10px !important;">{{$detalle['estado']}}</td>
                        <td style="border-color: gray;font-size:10px !important;">{{$detalle['minimo']}}</td>
                        <td style="border-color: gray;font-size:10px !important;">{{$detalle['maximo']}}</td>
                      </tr>
                      @endforeach
                    </tr>
                  @endforeach

                </tbody>
              </table>
              @endif
            </div>
        </div>
        <div style="border-collapse: collapse;position:absolute;" >
          <br>
          <p style="border: 0px;" >Observaciones:</p>
          @if($rel->nro_paginas == $pagina['count_nro_pagina'])
            <p style="border: 0px;" >{{$rel->observaciones}}</p>
            <p></p>
            <br>
            <p style="border: 0px;" >Firma y Aclaración Fiscalizador:</p>
            <p style="border: 0px;" >{{$rel->fiscalizador}}</p>
          @endif
          <p></p>
        </div>


      @endforeach

      </body>
</html>
