<?php echo e($appName); ?> - Restablecer contrasena

<?php
    $name = trim($user->name ?? '');
?>
<?php echo e($name !== '' ? 'Hola '.$name.',' : 'Hola,'); ?>


Recibimos una solicitud para restablecer la contrasena de tu cuenta. Usa el enlace para continuar:

<?php echo e($actionUrl); ?>


Este enlace expira en <?php echo e($expire); ?> minutos por seguridad.

Si no solicitaste este cambio, puedes ignorar este correo. Tu contrasena actual seguira siendo valida.

Ayuda: <?php echo e($supportEmail); ?>

<?php echo e($appUrl); ?>

<?php /**PATH /home/san7imo/Escritorio/Proyectos/dilo-deploy/resources/views/emails/password-reset-text.blade.php ENDPATH**/ ?>