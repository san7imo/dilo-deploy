<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <title>Invitación Dilo Records</title>
  </head>
  <body style="font-family: Arial, sans-serif; background: #0b0b0b; color: #f5f5f5; padding: 24px;">
    <h2 style="margin: 0 0 16px; color: #ffa236;">Invitación a tu panel de artista</h2>

    <p style="margin: 0 0 12px;">
      Hola{{ $inviteeName ? ', ' . $inviteeName : '' }}.
      Te invitamos a crear tu cuenta para revisar las canciones y regalías asociadas en Dilo Records.
    </p>

    @if($trackTitle)
      <p style="margin: 0 0 12px;">
        <strong>Canción asociada:</strong> {{ $trackTitle }}
      </p>
    @endif

    <p style="margin: 0 0 12px;">
      Este enlace vence el <strong>{{ $expiresAtText }}</strong>.
    </p>

    <p style="margin: 20px 0;">
      <a href="{{ $invitationUrl }}"
         style="display:inline-block;background:#ffa236;color:#111111;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:700;">
         Crear mi cuenta
      </a>
    </p>

    <p style="margin: 12px 0; color: #a3a3a3; font-size: 13px;">
      Si el botón no funciona, copia y pega este enlace en tu navegador:
    </p>
    <p style="margin: 0; color: #a3a3a3; font-size: 13px; word-break: break-all;">
      {{ $invitationUrl }}
    </p>
  </body>
</html>
