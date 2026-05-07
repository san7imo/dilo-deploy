@php
    $backgroundImage = isset($message)
        ? $message->embed(resource_path('js/Assets/Images/email/dilo-email.png'))
        : '';
    $logoImage = isset($message)
        ? $message->embed(resource_path('js/Assets/Images/Logos/logo-blanco.png'))
        : '';
    $artistName = $inviteeName ?: 'Artista';
@endphp
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitación Dilo Records</title>
  </head>
  <body style="margin:0; padding:0; background-color:#111111;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; mso-hide:all;">
      Crea tu cuenta para revisar tus canciones y royalties asociadas en Dilo Records.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; border-collapse:collapse; background-color:#111111;">
      <tr>
        <td align="center" style="padding:24px 12px;">
          <table
            role="presentation"
            width="100%"
            cellpadding="0"
            cellspacing="0"
            border="0"
            background="{{ $backgroundImage }}"
            style="width:100%; max-width:600px; border-collapse:collapse; background-color:#111111; background-image:url('{{ $backgroundImage }}'); background-position:center top; background-repeat:no-repeat; background-size:cover;"
          >
            <tr>
              <td style="padding:30px 28px 18px 28px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                  <tr>
                    <td align="right">
                      <img
                        src="{{ $logoImage }}"
                        alt="Dilo Records"
                        width="164"
                        style="display:block; width:164px; max-width:100%; height:auto; border:0;"
                      >
                    </td>
                  </tr>
                </table>
              </td>
            </tr>

            <tr>
              <td style="padding:78px 28px 24px 28px;">
                <table
                  role="presentation"
                  width="100%"
                  cellpadding="0"
                  cellspacing="0"
                  border="0"
                  style="max-width:440px; border-collapse:separate; background:rgba(235, 165, 55, 0.92); border-radius:34px;"
                >
                  <tr>
                    <td style="padding:34px 36px 18px 36px; font-family:Arial, sans-serif; color:#111111;">
                      <div style="margin:0 0 18px 0; font-size:26px; line-height:1.2; font-weight:800;">
                        Conoce tu panel<br>de artista
                      </div>

                      <div style="margin:0; font-size:16px; line-height:1.4;">
                        Hola, {{ $artistName }}.<br>
                        Te invitamos a registrarte en nuestra web para visualizar las canciones y royalties asociadas en Dilo Records.
                      </div>

                      @if($trackTitle)
                        <div style="margin:16px 0 0 0; font-size:14px; line-height:1.4; font-weight:700;">
                          Canción asociada: {{ $trackTitle }}
                        </div>
                      @endif

                      <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:28px auto 0 auto; border-collapse:collapse;">
                        <tr>
                          <td
                            align="center"
                            style="border:2px solid #ffffff; border-radius:14px; background-color:transparent;"
                          >
                            <a
                              href="{{ $invitationUrl }}"
                              style="display:inline-block; padding:14px 36px; font-family:Arial, sans-serif; font-size:18px; line-height:1.2; font-weight:700; color:#ffffff; text-decoration:none;"
                            >
                              Crear mi cuenta
                            </a>
                          </td>
                        </tr>
                      </table>

                      <div style="margin:16px 0 0 0; font-size:13px; line-height:1.4; text-align:center; color:#2d2418;">
                        Este enlace vence el {{ $expiresAtText }}
                      </div>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>

            <tr>
              <td style="padding:0 28px 34px 28px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                  <tr>
                    <td style="font-family:Arial, sans-serif; font-size:14px; line-height:1.5; color:#f3f3f3; text-align:center;">
                      Si el botón no funciona usa este enlace en tu navegador:
                    </td>
                  </tr>
                  <tr>
                    <td style="padding-top:4px; font-family:Arial, sans-serif; font-size:14px; line-height:1.5; color:#f3f3f3; text-align:center; word-break:break-all;">
                      <a href="{{ $invitationUrl }}" style="color:#f3f3f3; text-decoration:underline;">{{ $invitationUrl }}</a>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
