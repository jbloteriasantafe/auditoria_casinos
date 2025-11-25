<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Denuncias seleccionadas</title>
  <style>
    @page { margin: 16mm 14mm; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color:#222; }
    h1 { font-size: 16px; margin: 0 0 10px; }
    table { width:100%; border-collapse: collapse; }
    thead th { background:#f0f0f0; border:1px solid #999; padding:6px 4px; font-weight:700; }
    tbody td { border:1px solid #999; padding:6px 4px; vertical-align: top; }
    td, th { word-wrap: break-word; overflow-wrap: break-word; }
    .nowrap { white-space: nowrap; }
    .center { text-align: center; }
  </style>
</head>
<body>
  <h1>Denuncias seleccionadas</h1>
  <table>
    <thead>
      <tr>
        <th class="nowrap" style="width:11%">Fecha</th>
        <th style="width:16%">Usuario de página</th>
        <th style="width:14%">Red/Plataforma</th>
        <th style="width:25%">Link de la página</th>
        <th class="center" style="width:9%">Denuncia Alea</th>
        <th class="center" style="width:9%">Cant. Denuncias</th>
        <th style="width:8%">Estado</th>
        <th style="width:8%">Denunciado en</th>
      </tr>
    </thead>
    <tbody>
      @if(!empty($rows))
        @foreach($rows as $r)
          <tr>
            <td class="nowrap">{{ $r['fecha'] }}</td>
            <td>{{ $r['user_pag'] }}</td>
            <td>{{ $r['plataforma'] }}</td>
            <td>{{ $r['link_pagina'] }}</td>
            <td class="center">{{ $r['denunciada'] }}</td>
            <td class="center">{{ $r['cant_denuncias'] }}</td>
            <td>{{ $r['estado'] }}</td>
            <td>{{ $r['lugar'] }}</td>
          </tr>
        @endforeach
      @else
        <tr><td colspan="8" class="center">Sin registros.</td></tr>
      @endif
    </tbody>
  </table>
</body>
</html>
