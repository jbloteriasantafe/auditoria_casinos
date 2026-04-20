<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Notas Unificadas - Exportación</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #333; }
        .header-box { background: #667eea; color: white; padding: 12px 20px; border-radius: 8px; margin-bottom: 10px; text-align: center; }
        .header-box h2 { margin: 0 0 3px 0; font-size: 16px; color: white; }
        .header-box .fecha-export { font-size: 9px; color: rgba(255,255,255,0.85); margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th { background: #2c3e50; color: white; padding: 4px 3px; text-align: left; font-size: 7px; }
        td { padding: 3px; border: 1px solid #ddd; vertical-align: top; font-size: 7px; word-wrap: break-word; }
        tr:nth-child(even) { background: #f9f9f9; }
        .badge-mkt { background: #3b82f6; color: white; padding: 1px 4px; border-radius: 3px; font-size: 6px; }
        .badge-fisc { background: #10b981; color: white; padding: 1px 4px; border-radius: 3px; font-size: 6px; }
        .estado { padding: 1px 4px; border-radius: 3px; font-size: 6px; color: white; }
        .comentario-item { margin-bottom: 3px; padding: 2px; background: #fef3c7; border-radius: 2px; font-size: 6px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header-box">
        <h2>Notas Unificadas</h2>
        <div class="fecha-export">Exportado el {{ date('d/m/Y H:i') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:5%">Nro de Nota</th>
                <th style="width:3%">Rama</th>
                <th style="width:7%">Tema del Evento</th>
                <th style="width:5%">Tipo Evento</th>
                <th style="width:5%">Origen</th>
                <th style="width:5%">Fecha Recepción</th>
                <th style="width:4%">Categoría</th>
                <th style="width:6%">Adj. Solicitud</th>
                <th style="width:6%">Adj. Diseño/Varios</th>
                <th style="width:5%">Adj. Bases y Cond.</th>
                <th style="width:6%">Adj. Inf. Técnico</th>
                <th style="width:5%">Fecha Inicio</th>
                <th style="width:5%">Fecha Fin</th>
                <th style="width:4%">Fecha Ref.</th>
                <th style="width:3%">Año</th>
                <th style="width:4%">Estado</th>
                <th style="width:8%">Comentarios</th>
                <th style="width:5%">Notas Aprob.</th>
                <th style="width:5%">Fecha Modif.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr>
                <td>{{ $row['nro_nota'] }}</td>
                <td><span class="{{ $row['tipo_rama'] == 'MKT' ? 'badge-mkt' : 'badge-fisc' }}">{{ $row['tipo_rama'] }}</span></td>
                <td>{{ $row['tema'] }}</td>
                <td>{{ $row['tipo_evento'] }}</td>
                <td>{{ $row['origen'] }}</td>
                <td>{{ $row['fecha_recepcion'] }}</td>
                <td>{{ $row['categoria'] }}</td>
                <td>{{ $row['adj_solicitud'] }}</td>
                <td>{{ $row['adj_diseno'] }}</td>
                <td>{{ $row['adj_bases'] }}</td>
                <td>{{ $row['adj_informe'] }}</td>
                <td>{{ $row['fecha_inicio'] }}</td>
                <td>{{ $row['fecha_fin'] }}</td>
                <td>{{ $row['fecha_referencia'] }}</td>
                <td>{{ $row['anio'] }}</td>
                <td>{{ $row['estado'] }}</td>
                <td style="font-size:6px;">{!! $row['comentarios'] !!}</td>
                <td>{{ $row['notas_aprobacion'] }}</td>
                <td>{{ $row['fecha_modif'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
