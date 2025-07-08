<!DOCTYPE html>
<html>
<html>
  <head>
    <meta charset="utf-8">

    <!-- <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> -->

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- <link href="css/bootstrap.min.css" rel="stylesheet"> -->

    <link href="css/estiloPlanillaLandscape.css" rel="stylesheet">
  </head>
  <body>


        <div class="encabezadoImg">
              <img src="img/logos/banner_2024_landscape.png" width="900">
              <h2><span>| Informe de eventualidades observadas en sala</span></h2>
        </div>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th { border: 1px solid #000; padding: 5px; text-align: left; background-color: #FA6600}
    td { border: 1px solid #000; padding: 5px; text-align: left; }
    .encabezado { width: 100%; margin-bottom: 20px; }
    .encabezado img { float: right; height: 60px; }
    .titulo { font-weight: bold; font-size: 16px; }
    .opciones {background-color: #c2c4c4}
  </style>
</head>
<body>
<table>
  <thead>
    <tr>
  <th><strong>Fecha:</strong> {{ $eventualidad->fecha_toma }}</th>
  <th><strong>Turno:</strong> {{ $eventualidad->turno->nro_turno ?? 'Desconocido' }}</th>
  <th><strong>Horario:</strong> {{ $eventualidad->horario }}</th>
  <th><strong>Casino:</strong> {{ $eventualidad->casino->nombre ?? 'Desconocido' }}</th>
    </tr>
  </thead>
</table>
<table>
  <thead>
    <tr>
      <th><strong>Fiscalizador/es:</strong> {{ $eventualidad->otros_fiscalizadores }} </th>
    </tr>
  </thead>
</table>
  @php
  $nombresProcedimientos = [
    'Toma de Contadores',
    'Contadores a Pedido',
    'Toma de Progresivos',
    'Control Ambiental',
    'Control de Layout Total',
    'Control de Layout Parcial',
    'Egreso y Reingreso de MTM',
    'Informes de Turnos Extras',
    'Relevamiento Torneo de Poker',
    'Bingo Tradicional',
    'Solicitudes de Reemplazo / Licencia',
    'Solicitud de Autoexclusión',
    'Valores de Apuesta de Mesas de Paño',
  ];
@endphp

<br/>

<table>
  <thead>
    <tr>
      <th class="opciones">Procedimientos realizados</th>
      <th class="opciones">✔</th>
      <th class="opciones">*</th>
      <th class="opciones">Observaciones</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($nombresProcedimientos as $i => $nombre)
      @php
        $p = $eventualidad->procedimientos[$i];
      @endphp
      <tr>
        <td>{{ $nombre }}</td>
        <td>{{ $p['estado'] === '✔' ? '✔' : '' }}</td>
        <td>{{ $p['estado'] === '*' ? '*' : '' }}</td>
        <td>{{ $p['observacion'] ?? '' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<br/>

<table>
  <tr>
      <th class="opciones"><strong>Menores en sala:</strong></th>
      <th class="opciones"><strong>Fumadores:</strong></th>
      <th class="opciones"><strong>Boletín adjunto:</strong></th>
</tr>
<tr>
      <td> {{ $eventualidad->menores }} </td>
      <td> {{ $eventualidad->fumadores }} </td>
      <td> {{ $eventualidad->boletin_adjunto }} </td>
</tr>
</table>

<br/>
<table>
  <tr><th class="opciones">Observaciones:</th></tr>
  <tr><td><p>{{ $eventualidad->observaciones }}</p></td></tr>
</table>
</body>
</html>
