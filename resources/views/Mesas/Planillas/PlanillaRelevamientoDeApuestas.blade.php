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

.separador {
  line-height: 5px;
  font-size: 5px !important;
  background-color: gray;
}
.separador2 {
  line-height: 5px;
  font-size: 5px !important;
  background-color: white;
  border-width: 0px; 
}

.mesa td {
  border-color: gray;
  line-height: 10px;
  font-size: 10px !important;
  vertical-align: middle;
  text-align: center;
  overflow-wrap: anywhere;
}

.juego {
  border-width:2px;
  border-color:#757575;
  vertical-align: middle;
  text-align: center;
  overflow-wrap: anywhere;
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
      @if($loop->first !== true)
        <div style="page-break-after:always;"></div>
      @endif
        <div class="encabezadoImg">
          <!-- ATENCIOON no borrar el public_path, porque si lo hardcodeamos
               en realidad hay 2 valores distintos de getcwd
               Por lo que cuando accedemos desde imprimir planilla desde la web
               nos devuelve por ejemplo auditoria_casinos y desde la tarea programada
               nos devuelve por ejemplo auditoria_casinos/public, por loque en uno de los
               dos seria erroneo, la forma mas compatible es llamar a esa funcion
               que nos de el directorio publico
          -->
          <img src="{{public_path()}}/img/logos/banner_nuevo2_landscape.png" width="900">
          <h2><span>RMES03 | Relevamiento de valores de apuestas de Mesas de Paño.</span></h2>
        </div>
        <div class="camposTab titulo" style="right:250px;">FECHA PLANILLA</div>
        <div class="camposInfo" style="right:261px;"></span>{{$rel->fecha}}</div>
        <table style="table-layout:fixed;width:100%">
          <thead>
            <tr class="headers-rel">
              <th>FECHA EJECUCIÓN</th>
              <td>{{$rel->fecha_backup}}</td>
              <th>TURNO</th>
              <td>{{$rel->turno}}</td>
              <th>HORA PROPUESTA</th>
              <td>{{$rel->hora_propuesta}}</td>
              <th>HORA EJECUCIÓN</th>
              <td>{{$rel->hora_ejecucion}}</td>
            </tr>
          </thead>
        </table>
        
        <table style="table-layout:fixed;width:100%">
          <tr>
            <td style="padding: 0;border: 0;vertical-align: top;">
              <table style="table-layout:fixed;width: 100%";>
                <thead>
                  <tr class="headers-juegos">
                    <th style="width: 20%;">JUEGO</th>
                    <th>CÓDIGO</th>
                    <th>MONEDA</th>
                    <th>POS.</th>
                    <th>ESTADO (A/C/T)</th>
                    <th>MÍNIMA</th>
                    <th>MÁXIMA</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($pagina['izquierda'] as $myj)
                  <tr>
                    <td class="juego" rowspan="{{count($myj['mesas'])+1}}">{{$myj['juego']}}</td>
                    <td colspan="6" class="separador">&nbsp;</td>
                    @foreach($myj['mesas'] as $detalle)
                    <tr class="mesa">
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
                    <td colspan="7" class="separador2">&nbsp;</td>
                  </tr>
                  @foreach($rel->totales['totales'] as $t)
                  <tr>
                    <td style="border: 0;font-size:13px !important;">&nbsp;</td>
                    <td rowspan="2" colspan="2" style="font-size:13px !important;">{{$t}}</td>
                    <td rowspan="2" colspan="4" style="border-bottom-width: 0px;font-size:13px !important;">&nbsp;</td>
                  </tr>
                  <tr>
                    <td style="border: 0;font-size:13px !important;">&nbsp;</td>
                  </tr>
                  @endforeach
                
                  @endif
                </tbody>
              </table>
            </td>
            <td style="padding: 0;border: 0;vertical-align: top;">
              @if($pagina['derecha'] !== null)
              <table style="table-layout:fixed;width: 100%";>
                <thead>
                  <tr class="headers-juegos">
                    <th style="width: 22%;">JUEGO</th>
                    <th>CÓDIGO</th>
                    <th>MONEDA</th>
                    <th>POS.</th>
                    <th>ESTADO (A/C/T)</th>
                    <th>MÍNIMA</th>
                    <th>MÁXIMA</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($pagina['derecha'] as $myj)
                  <tr>
                    <td class="juego" rowspan="{{count($myj['mesas'])+1}}">{{$myj['juego']}}</td>
                    <td colspan="6" class="separador">&nbsp;</td>
                    @foreach($myj['mesas'] as $detalle)
                    <tr class="mesa">
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
                    <td colspan="7" class="separador2">&nbsp;</td>
                  </tr>
                  @foreach($rel->totales['totales'] as $t)
                  <tr>
                    <td style="border: 0;font-size:13px !important;">&nbsp;</td>
                    <td rowspan="2" colspan="3" style="font-size:13px !important;">{{$t}}</td>
                    <td rowspan="2" colspan="3" style="font-size:13px !important;">&nbsp;</td>
                  </tr>
                  <tr>
                    <td style="border: 0;font-size:13px !important;">&nbsp;</td>
                  </tr>
                  @endforeach
                  
                  @endif
                </tbody>
              </table>
              @else
              &nbsp;
              @endif
            </td>
          </tr>
          <tr>
            <td style="padding: 0;border: 0;vertical-align: top;" colspan="2">
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
            </td>
          </tr>
        </table>
      @endforeach
    </body>
</html>
