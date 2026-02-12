<?php

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Controller;
use App\Mail\ContactFormMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function show()
    {
        $contact = [
            'email' => config('site.email', 'info@dilorecords.com'),
            'phone' => config('site.phone', '+34 608 52 94 93'),
            'address' => config('site.address', 'España'),
            'social' => [
                'instagram' => config('site.social.instagram', 'https://www.instagram.com/dilorecords'),
                'facebook' => config('site.social.facebook', null),
                'tiktok' => config('site.social.tiktok', null),
                'youtube' => config('site.social.youtube', null),
                'spotify' => config('site.social.spotify', 'https://www.spotify.com/dilorecords'),
                'x' => config('site.social.x', 'https://www.twitter.com/dilorecords'),
            ],
        ];

        return Inertia::render('Public/Contact', [
            'contact' => $contact,
        ]);
    }

    public function submit(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        Mail::to('diana@dilorecords.com')
            ->send(new ContactFormMail($data));

        return back()->with('success', 'Tu mensaje fue enviado correctamente.');
    }
}
