<?php

namespace App\Mail;

use App\Models\RoyaltyPayoutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RoyaltyPayoutRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly RoyaltyPayoutRequest $payoutRequest
    ) {
    }

    public function build()
    {
        return $this->subject('Solicitud de pago de regalías')
            ->view('emails.royalty-payout-requested');
    }
}
