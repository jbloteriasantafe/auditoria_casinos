<!DOCTYPE html>
<html>
<head>
    <title>Disposición</title>
    <style>
        body { font-family: sans-serif; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { width: 150px; }
        .content { font-size: 14px; line-height: 1.6; text-align: justify; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <!-- <img src="{{ public_path('img/logo.png') }}" class="logo"> -->
        <h3>CAJA DE ASISTENCIA SOCIAL - LOTERIA DE SANTA FE</h3>
    </div>

    <div class="content">
        {!! $disposicion->cuerpo_considerandos !!}
    </div>

    <div class="footer">
        Generado por Siste.mon - {{ date('d/m/Y H:i') }}
    </div>
</body>
</html>
