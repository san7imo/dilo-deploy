@php
    $name = trim($user->name ?? '');
    $greeting = $name !== '' ? 'Hola '.$name.',' : 'Hola,';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restablecer contrasena</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f5f7; }
        table { border-spacing: 0; }
        img { border: 0; }
    </style>
</head>
<body>
    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
        Restablece tu contrasena en {{ $appName }} de forma segura.
    </div>
    <table role="presentation" width="100%" style="background-color:#f4f5f7; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:620px; background-color:#ffffff; border:1px solid #e6e9ee; border-radius:12px; overflow:hidden;">
                    <tr>
                        <td style="background:#111827; padding:24px 32px; font-family:'Trebuchet MS', 'Segoe UI', Arial, sans-serif; color:#ffffff;">
                            <div style="font-size:18px; letter-spacing:2px; text-transform:uppercase;">{{ $appName }}</div>
                            <div style="font-size:14px; opacity:0.8; margin-top:4px;">Solicitud de restablecimiento</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px; font-family:'Trebuchet MS', 'Segoe UI', Arial, sans-serif; color:#1f2933;">
                            <p style="margin:0 0 16px; font-size:16px; line-height:1.6;">{{ $greeting }}</p>
                            <p style="margin:0 0 16px; font-size:16px; line-height:1.6;">
                                Recibimos una solicitud para restablecer la contrasena de tu cuenta. Haz clic en el boton para continuar.
                            </p>
                            <p style="margin:0 0 24px; text-align:center;">
                                <a href="{{ $actionUrl }}" style="background:#2563eb; color:#ffffff; padding:12px 24px; text-decoration:none; border-radius:6px; display:inline-block; font-weight:600;">
                                    Restablecer contrasena
                                </a>
                            </p>
                            <p style="margin:0 0 16px; font-size:14px; color:#52606d;">
                                Este enlace expira en {{ $expire }} minutos por seguridad.
                            </p>
                            <p style="margin:0 0 12px; font-size:14px; color:#52606d;">
                                Si el boton no funciona, copia y pega este enlace en tu navegador:
                            </p>
                            <p style="margin:0 0 24px; font-size:12px; line-height:1.6; color:#1f2933; word-break:break-all;">
                                {{ $actionUrl }}
                            </p>
                            <p style="margin:0; font-size:14px; color:#52606d;">
                                Si no solicitaste este cambio, puedes ignorar este correo. Tu contrasena actual seguira siendo valida.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px; background:#f8fafc; font-family:'Trebuchet MS', 'Segoe UI', Arial, sans-serif; color:#7b8794; font-size:12px; line-height:1.6;">
                            <div>Necesitas ayuda? Responde a este correo o escribenos a {{ $supportEmail }}.</div>
                            <div style="margin-top:8px;">{{ $appName }} - {{ $appUrl }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
