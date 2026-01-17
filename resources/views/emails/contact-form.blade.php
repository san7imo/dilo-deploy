<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <title>Contacto Dilo Records</title>
  </head>
  <body style="font-family: Arial, sans-serif; background: #0b0b0b; color: #f5f5f5; padding: 24px;">
    <h2 style="margin: 0 0 16px; color: #ffa236;">Nuevo mensaje de contacto</h2>
    <p style="margin: 0 0 12px;"><strong>Nombre:</strong> {{ $name }}</p>
    <p style="margin: 0 0 12px;"><strong>Email:</strong> {{ $email }}</p>
    @if($phone)
      <p style="margin: 0 0 12px;"><strong>Teléfono:</strong> {{ $phone }}</p>
    @endif
    <p style="margin: 0 0 12px;"><strong>Asunto:</strong> {{ $subjectLine }}</p>
    <div style="margin-top: 16px; padding: 16px; background: #141414; border-radius: 8px;">
      <p style="margin: 0; white-space: pre-line;">{{ $content }}</p>
    </div>
  </body>
</html>
