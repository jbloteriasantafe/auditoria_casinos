<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { width: 80%; margin: 20px auto; border: 1px solid #ddd; padding: 20px; border-radius: 10px; }
        .button { 
            display: inline-block; 
            padding: 10px 20px; 
            background-color: #3490dc; 
            color: #ffffff; 
            text-decoration: none; 
            border-radius: 5px; 
            margin-top: 15px;
        }
        .footer { font-size: 0.8em; color: #777; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Hola,</h2>
        <p>Has recibido este correo porque se solicitó un restablecimiento de contraseña para tu cuenta: <strong>{{ $email }}</strong>.</p>
        <p>Su código de validación es:</p>
        <h3>{{ $codigo }}</h3>
        
        <p>O haz clic en el siguiente botón para restablecerla:</p>
        <a href="{{ $link }}" class="button" style="color: white;">Restablecer Contraseña</a>
        
        <p>Si no solicitaste este cambio, puedes ignorar este correo de forma segura.</p>
        
        <hr>
        <div class="footer">
            <p>Si tienes problemas con el botón, copia y pega este enlace en tu navegador:</p>
            <p>{{ $link }}</p>
        </div>
    </div>
</body>
</html>
