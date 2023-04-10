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
  background-color: #757575;
  font-size: 1px !important;
  padding: 0;
  margin: 0;
  border: 0;
  border-right: 1px solid #757575;
}

.separador2 {
  background-color: white;
  border-width: 0px; 
  line-height: 10px !important;
  font-size: 10px !important;
}

.mesa td {
  border-color: gray;
  line-height: 10px !important;
  font-size: 10px !important;
  vertical-align: middle;
  text-align: center;
  overflow-wrap: anywhere;
  word-break: break-all;
}

.juego {
  border-width:2px;
  border-color:#757575;
  vertical-align: middle;
  text-align: center;
  overflow-wrap: anywhere;
  word-break: break-all;
  font-size: 13px;
  padding: 1px;
}

#tablaFinal th,#tablaFinal td {
  border: 0;
  padding: 0;
  text-align: center;
  vertical-align: top;
  font-size: 11px !important;
}

</style>

  <head>
    <title></title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="{{public_path()}}/css/estiloPlanillaLandscapeMesas.css" rel="stylesheet">
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
          <img src="{{public_path()}}/img/logos/banner_nuevo2_landscape.png" width="1090">
          <h2><span>RMES03 | Relevamiento de valores de apuestas de Mesas de Paño.</span></h2>
        </div>
        <div class="camposTab titulo" style="right:50px;">FECHA PLANILLA</div>
        <div class="camposInfo" style="right:61px;"></span>{{$rel->fecha}}</div>
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
            @foreach($pagina as $juegos)
            <td style="padding: 0;border: 0;vertical-align: top;">
              @if($juegos === null)
              &nbsp;
              @else
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
                  @foreach($juegos as $myj)
                  
                  <tr>
                    <td class="juego" rowspan="{{count($myj['mesas'])+1}}">{{$myj['juego']}}</td>
                    <td class="separador" colspan="6">&nbsp;</td>
                  </tr>
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
                  
                  @endforeach
                </tbody>
              </table>
              @endif
            </td>
            @endforeach
          </tr>
        </table>
        @if($loop->last)
        <table id="tablaFinal" style="table-layout:fixed;width:100%;padding-top: 5px;">
          <tr>
            @foreach($rel->totales as $t)
            <th>{{$t['texto']}}</th>
            @endforeach
          </tr>
          <tr>
            @foreach($rel->totales as $t)
            <td>
				@if(empty($t['val']))
				&nbsp;
				@else
				{{$t['val']}}
				@endif
            </td>
            @endforeach
          </tr>
          <tr>
            <th>Observaciones</th>
            <th>Firma y Aclaración Fiscalizador</th>
          </tr>
          <tr>
			<td>
				@if(empty($rel->observaciones))
				&nbsp;
				@else
				{{$rel->observaciones}}
				@endif
            </td>
            <td>
				@if(empty($rel->fiscalizador))
				&nbsp;
				@else
				{{$rel->fiscalizador}}
				@endif
            </td>
          </tr>
        </table>
        @endif
      @endforeach
    </body>
</html>
