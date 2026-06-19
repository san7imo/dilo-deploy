{{ $appName }} - Recupera tu acceso

@php
    $name = trim($user->stage_name ?? $user->name ?? '');
@endphp
Hola, {{ $name !== '' ? $name : 'Artista' }}.

Recibimos una solicitud para restablecer la contrasena de tu cuenta en Dilo Records.

Usa este enlace para continuar:
{{ $actionUrl }}

Este enlace vence en {{ $expire }} minutos.

Si no solicitaste este cambio, puedes ignorar este correo. Tu contrasena actual seguira siendo valida.

Ayuda: {{ $supportEmail }}
{{ $appUrl }}
