<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExternalArtistInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $invitationUrl,
        public readonly ?string $trackTitle,
        public readonly ?string $inviteeName,
        public readonly string $expiresAtText
    ) {
    }

    public function build()
    {
        return $this->subject('Invitación Dilo Records · Acceso a regalías')
            ->view('emails.external-artist-invitation');
    }
}
