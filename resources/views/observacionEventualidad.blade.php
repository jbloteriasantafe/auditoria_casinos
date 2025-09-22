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
              <h2><span>| Observación de la eventualidad |</span></h2>
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
      <?=
      $observacion->eventualidad->fecha_toma = substr($observacion->eventualidad->fecha_toma, 0, 11);
      $observacion->eventualidad->horario = substr($observacion->eventualidad->horario,0, 16);
      $horaIn = substr($observacion->eventualidad->horario,0,5);
      $horaFin = substr($observacion->eventualidad->horario,11,16);
      $observacion->eventualidad->horario = $horaIn . ' a '.$horaFin;
      ?>
  <th><strong>Fecha:</strong> {{ $observacion->eventualidad->fecha_toma }}</th>
  <th><strong>Turno:</strong> {{ $observacion->eventualidad->turno->nro_turno ?? 'Desconocido' }}</th>
  <th><strong>Horario:</strong> {{ $observacion->eventualidad->horario }}</th>
  <th><strong>Casino:</strong> {{ $observacion->eventualidad->casino->nombre ?? 'Desconocido' }}</th>
    </tr>
  </thead>
</table>
<br/>
<br/>
{{$observacion->observacion}}

</body>
</html>
