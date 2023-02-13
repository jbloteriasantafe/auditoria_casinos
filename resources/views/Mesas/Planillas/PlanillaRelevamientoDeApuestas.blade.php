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

.headers-rel th, .headers-rel td {
  font-size: 11px !important;
  border-color: gray;
  width: 10px !important;
}

.headers-juegos th {
  border-color: gray;
  text-align: center !important;
  font-size: 11px !important;
  height: 30px !important
}

.forzar-altura-14px {
  line-height: 14px !important;
}

.mesa td {
  border-color: gray;
  font-size: 10px !important;
}
</style>

  <head>
    <title></title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="public/css/estiloPlanillaLandscapeMesas.css" rel="stylesheet">
  </head>
  <body>
    @foreach($rel->paginas as $pagina)
      @if(!$loop->first)
        <div style="page-break-after:always;"></div>
      @endif
        <div class="encabezadoImg">
              <img src="public/img/logos/banner_nuevo2_landscape.png" width="1090">
              <h2><span>RMES03 | Relevamiento de valores de apuestas de Mesas de Paño.</span></h2>
        </div>
        <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
        <div class="camposInfo" style="right:0px;"></span>{{$rel->fecha}}</div>
        <table style="border-collapse: collapse;  table-layout:fixed; width:100%">
          <tr class="headers-rel" style="width:100%">
            <th>FECHA EJECUCIÓN</th>
            <td>{{$rel->fecha_backup}}</td>
            <th>TURNO</th>
            <td>{{$rel->turno}}</td>
            <th>HORA PROPUESTA</th>
            <td>{{$rel->hora_propuesta}}</td>
            <th>HORA EJECUCIÓN</th>
            <td>{{$rel->hora_ejecucion}}</td>
          </tr>
        </table>
        <div style="border-collapse: collapse;position:relative" >
          <div style="border-collapse: collapse;float: left; width: 50%;">
              <table style="width:100%;"  class="table table-responsive">
                <tbody>
                  <tr class="headers-juegos">
                    <th>JUEGO</th>
                    <th>CÓDIGO</th>
                    <th>MONEDA</th>
                    <th>POS.</th>
                    <th>ESTADO (A/C/T)</th>
                    <th>MÍNIMA</th>
                    <th>MÁXIMA</th>
                  </tr>

                  @foreach($pagina['izquierda'] as $myji)
                  <tr>
                    <td vertical-align="middle" style="border-width:2px; border-color:#757575;" rowspan="{{count($myji['mesas'])+1}}">{{$myji['juego']}}</td>
                    <td colspan="6" style="background-color: gray; font-size:1px !important;">&nbsp;</td>
                    @foreach($myji['mesas'] as $detalle)
                    <tr class="mesa forzar-altura-14px">
                      <td>{{$detalle['codigo_mesa']}}</td>
                      <td>{{$detalle['siglas']}}</td>
                      <td>{{$detalle['posiciones']}}</td>
                      <td>{{$detalle['estado']}}</td>
                      <td>{{$detalle['minimo']}}</td>
                      <td>{{$detalle['maximo']}}</td>
                    </tr>
                    @endforeach
                  </tr>
                  @endforeach
                  
                  @if($loop->last && $rel->totales['columna'] == 'izquierda')
                  
                  <tr><!-- Separador -->
                    <td colspan="7" style="border-width: 0px; border-color: white;">&nbsp;</td>
                  </tr>
                  @foreach($rel->totales['totales'] as $t)
                  <tr>
                    <td rowspan="2" style="border-width: 0px;font-size:13px !important;">&nbsp;</td>
                    <td rowspan="2" colspan="2" style="font-size:13px !important;">{{$t}}</td>
                    <td             colspan="4" style="border-bottom-width: 0px;font-size:13px !important;">&nbsp;</td>
                  </tr>
                  <tr>
                    <td             colspan="4" style="border-top-width: 0px;font-size:13px !important;">&nbsp;</td>
                  </tr>
                  @endforeach
                  
                  @endif
                  
                </tbody>
              </table>
            </div>
            <div style="float: right; width: 50%;position:absolute;" >
              @if( $pagina['derecha'] !== null)
              <table style="width:100%;position:relative" class="table table-responsive" >
                <tbody >
                  <tr class="headers-juegos" style="float:right;">
                    <th>JUEGO</th>
                    <th>CÓDIGO</th>
                    <th>MONEDA</th>
                    <th>POS.</th>
                    <th>ESTADO (A/C/T)</th>
                    <th>MÍNIMA</th>
                    <th>MÁXIMA</th>
                  </tr>

                  @foreach($pagina['derecha'] as $myjd)
                  <tr>
                    <td vertical-align="middle" style="border:2px solid #757575 !important;" rowspan="{{count($myjd['mesas'])+1}}">{{$myjd['juego']}}</td>
                    <td colspan="6" style="background-color: gray; font-size:1px !important;">&nbsp;</td>
                    @foreach($myjd['mesas'] as $detalle)
                    <tr class="mesa forzar-altura-14px">
                      <td>{{$detalle['codigo_mesa']}}</td>
                      <td>{{$detalle['siglas']}}</td>
                      <td>{{$detalle['posiciones']}}</td>
                      <td>{{$detalle['estado']}}</td>
                      <td>{{$detalle['minimo']}}</td>
                      <td>{{$detalle['maximo']}}</td>
                    </tr>
                    @endforeach
                  </tr>
                  @endforeach
                  
                  @if($loop->last && $rel->totales['columna'] == 'derecha')
                  
                  <tr><!-- Separador -->
                    <td colspan="7" style="border-width: 0px; border-color: white;">&nbsp;</td>
                  </tr>
                  @foreach($rel->totales['totales'] as $t)
                  <tr>
                    <td rowspan="2" style="border-width: 0px;font-size:13px !important;">&nbsp;</td>
                    <td rowspan="2" colspan="2" style="font-size:13px !important;">{{$t}}</td>
                    <td             colspan="4" style="border-bottom-width: 0px;font-size:13px !important;">&nbsp;</td>
                  </tr>
                  <tr>
                    <td             colspan="4" style="border-top-width: 0px;font-size:13px !important;">&nbsp;</td>
                  </tr>
                  @endforeach
                  
                  @endif
                  
                </tbody>
              </table>
              @endif
            </div>
        </div>
        <div style="border-collapse: collapse;position:absolute;" >
          <br>
          <p style="border: 0px;" >Observaciones:</p>
          @if($loop->last)
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
