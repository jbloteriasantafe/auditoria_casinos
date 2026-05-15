<!DOCTYPE html>

<html>

<style>
body {
  font-size: 0.8em;
}
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  table-layout: fixed;
  word-wrap: break-word !important;
}

td, th {
  border: 1px solid #dddddd;
}
th {
  text-align: center;
}
td {
  text-align: right;
}

tr:nth-child(even) {
  background-color: #dddddd;
}

tr > td.bleft, tr > th.bleft {
  border-left: 1px solid black !important;
}
tr > td.bright, tr > th.bright {
  border-right: 1px solid black !important;
}
tr > td.btop, tr > th.btop {
  border-top: 1px solid black !important;
}
tr > td.bbottom, tr > th.bbottom {
  border-bottom: 1px solid black !important;
}
tr > td.b, tr > th.b {
  border: 1px solid black !important;
}
tr > td.nob, tr > th.nob {
  border: unset !important;
}
tr, th, td {
  background: white;
}

thead th {
  font-weight: bolder;
}
tbody th {
  font-weight: normal;
}
</style>

<?php
$DEC = function($val){
  return $val === null? '-' : \App\Http\Controllers\CanonController::formatear_decimal($val);
};
$PJE = function($val){
  return $val === null? '-' : \App\Http\Controllers\CanonController::formatear_decimal($val).'%';
};
?>

<head>
  <meta charset="utf-8">
  <title></title>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
</head>

<style>
  @page {
    margin: 150px 50px;
  }
  header {
    position: fixed;
    top: -150px;
    left: 0px;
    right: 0px;
  }
  footer {
    position: fixed;
    bottom: 0px;
    left: 0px;
    right: 0px;
  }
  main {
    position: fixed;
    top: 0px;
    height: 500px;
    overflow: clip;
    font-size: 0.87rem;
  }
</style>

<body style="width: 100%;height: 100%;">
  <header>
    <img src="img/logos/hoja_membretada_cabeza_2026.png" style="width: 100%;">
  </header>
  <footer>
    <img src="img/logos/hoja_membretada_pie_2026.png" style="width: 100%;">
  </footer>
  <main style="width: 100%">
    <?php
      $titulo;
      if($casino == 'Rosario'){
        $titulo = "Casino $casino S.A.";
      }
      elseif($casino == 'Melincué'){
        $titulo = "Casino $casino S.A.";
      }
      elseif($casino == 'Santa Fe'){
        $titulo = "Casino Puerto $casino S.A.";
      }
      else{
        $titulo = $casino;
      }
    ?>
    <p style="text-align: center;"><b>Informe de Cálculo Canon Mensual&nbsp;&nbsp;—&nbsp;&nbsp;{{$titulo}}&nbsp;&nbsp;—&nbsp;&nbsp;{{$año}}/{{$mes}}</b></p>
    <h4>Canon Variable</h4>
    <div style="padding-left: 1em;">
      <table style="width: 100%;">
        <thead>
          <tr>
            <th>Detalle</th>
            <th>Beneficio</th>
            <th>Alícuota</th>
            <th>Determinado</th>
          </tr>
        </thead>
        <tbody>
          <?php $bbottom = array_keys($data['Variable'])[count($data['Variable'])-2]; ?>
          @foreach($data['Variable'] as $tvar => $dvar)
            @if($tvar != 'Total')
            <tr>
              <th>{{$tvar}}</td>
              <td>{{$DEC($dvar['beneficio'] ?? null)}}</td>
              <td>{{$PJE($dvar['alicuota'] ?? null)}}</td>
              <td class="{{$tvar == $bbottom? 'bbottom' : ''}}">{{$DEC($dvar['determinado'] ?? null)}}</td>
            </tr>
            @else
            <tr>
              <th class="nob">&nbsp;</th>
              <td>{{$DEC($dvar['beneficio'] ?? null)}}</td>
              <td class="nob">&nbsp;</td>
              <td class="b">{{$DEC($dvar['determinado'] ?? null)}}</td>
            </tr>
            @endif
          @endforeach
        </tbody>
      </table>
    </div>          
    <br>
    <h4>Canon Fijo</h4>
    <div style="padding-left: 1em;">
      <table style="width: 100%;">
        <thead>
          <tr>
            <th>Moneda</th>
            <th>Valor Mesa</th>
            <th>Tipo de cambio</th>
            <th>Valor Mesa (Pesos)</th>
          </tr>
        </thead>
        <tbody>
          @foreach($data['Fijo']['Monedas'] as $mon => $datamon)
          <tr>
            <th>{{$mon}}</th>
            <td>{{$DEC($datamon['valor'] ?? null)}}</td>
            <td>{{$DEC($datamon['cotizacion'] ?? null)}}</td>
            <td>{{$DEC($datamon['pesos'] ?? null)}}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
      <br>
      <table style="width: 22em;">
        <thead>
          <tr>
            <th>Valor Mensual</th>
            <td>{{$DEC($data['Valores']['mes'] ?? null)}}</td>
          </tr>
          <?php 
            $aclarar_dia = count($data['Valores']['dia']) > 1; 
            $aclarar_hora = count($data['Valores']['hora']) > 1; 
          ?>
          @foreach($data['Valores']['dia'] as $div => $val)
          <tr>
            <th>Valor Diario {{$aclarar_dia? ('('.$div.')') : ''}}</th>
            <td>{{$DEC($val ?? null)}}</td>
          </tr>
          @endforeach
          @foreach($data['Valores']['hora'] as $div => $val)
            <tr>
              <th>Valor Hora {{$aclarar_hora? ('('.$div.')') : ''}}</th>
              <td>{{$DEC($val ?? null)}}</td>
            </tr>
          @endforeach
        </thead>
      </table>
      
      <br>
      <table style="width: 22em;">
        <thead>
          <tr>
            <th class="b">Determinado</th>
            <td class="b">{{$DEC($data['Fijo']['determinado'] ?? null)}}</td>
          </tr>
        </thead>
      </table>
      
      <h4>Mesas</h4>
      <div style="padding-left: 1em;">
        <table style="width: 29em;">
          <thead>
            <tr>
              <th>Día semana</th>
              <th>Días</th>
              <th>Mesas</th>
            </tr>
          </thead>
          <tbody>
            <?php $mesas = $data['Fijo']['Mesas']; ?>
            <tr>
              <th>Lunes-Jueves</th>
              <td>{{$DEC($mesas['Lunes-Jueves']['dias'] ?? null)}}</td>
              <td>{{$DEC($mesas['Lunes-Jueves']['mesas'] ?? null)}}</td>
            </tr>
            <tr>
              <th>Viernes-Sábados</th>
              <td>{{$DEC($mesas['Viernes-Sábados']['dias'] ?? null)}}</td>
              <td>{{$DEC($mesas['Viernes-Sábados']['mesas'] ?? null)}}</td>
            </tr>
            <tr>
              <th>Domingos</th>
              <td>{{$DEC($mesas['Domingos']['dias'] ?? null)}}</td>
              <td>{{$DEC($mesas['Domingos']['mesas'] ?? null)}}</td>
            </tr>
            <tr>
              <th>Todos</th>
              <td>{{$DEC($mesas['Todos']['dias'] ?? null)}}</td>
              <td>{{$DEC($mesas['Todos']['mesas'] ?? null)}}</td>
            </tr>
            <tr>
              <th>Fijos</th>
              <td>{{$DEC($mesas['Fijos']['dias'] ?? null)}}</td>
              <td>{{$DEC($mesas['Fijos']['mesas'] ?? null)}}</td>
            </tr>
            <tr>
              <td class="nob" colspan="2">&nbsp;</td>
              <td>{{$DEC($mesas['Total']['mesas'] ?? null)}}</td>
            </tr>
          </tbody>
        </table>
        <br>
        <table style="width: 22em;">
          <thead>
            <tr>
              <th class="b">Determinado</th>
              <td class="b">{{$DEC($mesas['Total']['determinado'] ?? null)}}</td>
            </tr>
          </thead>
        </table>
      </div>
      
      <h4>Mesas Adicionales</h4>
      <div style="padding-left: 1em;">
        <table style="width: 100%;">
          <thead>
            <tr>
              <th>Detalle</th>
              <th>Horas</th>
              <th>Mesas</th>
              <th>Determinado</th>
            </tr>
          </thead>
          <tbody>
            <?php $bbottom = array_keys($data['Fijo']['Adicionales'])[count($data['Fijo']['Adicionales'])-2]; ?>
            @foreach($data['Fijo']['Adicionales'] as $tfa => $data_tfa)
            @if($tfa != 'Total')
            <tr>
              <?php $aclarar = $aclarar_dia || $aclarar_hora; ?>
              <th>{{$tfa}}{{$aclarar? ('('.$data_tfa['valor_hora'].')') : ''}}</th>
              <td>{{$DEC($data_tfa['horas'] ?? null)}}</td>
              <td>{{$DEC($data_tfa['mesas'] ?? null)}}</td>
              <td class="{{$tfa == $bbottom? 'bbottom' : ''}}">{{$DEC($data_tfa['determinado'] ?? null)}}</td>
            </tr>
            @else
            <tr>
              <td class="nob">&nbsp;</td>
              <td>{{$DEC($data_tfa['horas'] ?? null)}}</td>
              <td class="bright">{{$DEC($data_tfa['mesas'] ?? null)}}</td>
              <td class="b">{{$DEC($data_tfa['determinado'] ?? null)}}</td>
            </tr>
            @endif
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    
    <div style="width: 100%;">
      <div style="width: 43%;float: left;">
        <h4>Totales</h4>
        <div style="padding-left: 1em;width: 22em;">
          <table style="width: 100%;">
            <thead>
              <tr>
                <th class="b">Canon Físico</th>
                <td class="b">{{$DEC($data['Canon']['Físico'] ?? null)}}</td>
              </tr>
              <tr>
                <th class="b">Canon Online</th>
                <td class="b">{{$DEC($data['Canon']['Online'] ?? null)}}</td>
              </tr>
              <tr>
                <th class="b">Canon</th>
                <td class="b">{{$DEC($data['Canon']['Total'] ?? null)}}</td>
              </tr>
            </thead>
          </table>
        </div>
      </div>
      <div style="margin-left: 43%;width: 57%;float: right;">
        <h4>&nbsp;</h4>
        <table style="padding-left: 1em;width: 100%;">
          <thead>
            <tr>
              <td class="nob" style="text-align: left;width: 20%;">Firma</td>
              <td class="nob" style="text-align: left;width: 80%;">{{str_repeat('.',90)}}</td>
            </tr>
            <tr>
              <th class="nob">&nbsp;</th>
              <td class="nob">&nbsp;</td>
            </tr>
            <tr>
              <th class="nob">&nbsp;</th>
              <td class="nob">&nbsp;</td>
            </tr>
            <tr>
              <td class="nob" style="text-align: left;">Aclaración</td>
              <td class="nob" style="text-align: left;">{{str_repeat('.',90)}}</td>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </main>
</body>

</html>
