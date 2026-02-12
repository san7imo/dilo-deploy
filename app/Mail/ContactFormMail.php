<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject('Nuevo contacto desde Dilo Records')
            ->replyTo($this->data['email'], $this->data['name'])
            ->view('emails.contact-form', [
                'name' => $this->data['name'],
                'email' => $this->data['email'],
                'phone' => $this->data['phone'] ?? null,
                'subjectLine' => $this->data['subject'],
                'content' => $this->data['message'],
            ]);
    }
}
