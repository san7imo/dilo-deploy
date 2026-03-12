<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <title>Solicitud de pago de regalías</title>
  </head>
  <body style="font-family: Arial, sans-serif; background: #0b0b0b; color: #f5f5f5; padding: 24px;">
    <h2 style="margin: 0 0 16px; color: #ffa236;">Nueva solicitud de pago de regalías</h2>

    <p style="margin: 0 0 10px;">
      <strong>Artista:</strong> {{ $payoutRequest->requester_name }}
    </p>
    <p style="margin: 0 0 10px;">
      <strong>Correo:</strong> {{ $payoutRequest->requester_email }}
    </p>
    <p style="margin: 0 0 10px;">
      <strong>Monto solicitado:</strong> USD {{ number_format((float) $payoutRequest->requested_amount_usd, 2) }}
    </p>
    <p style="margin: 0 0 10px;">
      <strong>Fecha:</strong> {{ optional($payoutRequest->requested_at)->format('d/m/Y H:i') }}
    </p>

    <p style="margin: 16px 0 0; color: #a3a3a3; font-size: 13px;">
      Revisa esta solicitud desde el módulo de royalties en el panel de administración.
    </p>
  </body>
</html>
