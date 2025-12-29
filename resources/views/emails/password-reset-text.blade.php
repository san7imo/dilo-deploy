{{ $appName }} - Restablecer contrasena

@php
    $name = trim($user->name ?? '');
@endphp
{{ $name !== '' ? 'Hola '.$name.',' : 'Hola,' }}

Recibimos una solicitud para restablecer la contrasena de tu cuenta. Usa el enlace para continuar:

{{ $actionUrl }}

Este enlace expira en {{ $expire }} minutos por seguridad.

Si no solicitaste este cambio, puedes ignorar este correo. Tu contrasena actual seguira siendo valida.

Ayuda: {{ $supportEmail }}
{{ $appUrl }}
