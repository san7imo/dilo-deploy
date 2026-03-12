<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizerId = $this->route('organizer')?->id;

        return [
            'company_name' => [
                'required',
                'string',
                'max:180',
                Rule::unique('organizers', 'company_name')
                    ->ignore($organizerId)
                    ->whereNull('deleted_at'),
            ],
            'contact_name' => ['nullable', 'string', 'max:180'],
            'address' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'logo_url' => ['nullable', 'url', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
            'x_url' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'El nombre de la empresa es obligatorio.',
            'company_name.unique' => 'Ya existe un empresario con este nombre de empresa.',
            'email.email' => 'El correo no tiene un formato válido.',
            'logo_url.url' => 'La URL del logo no es válida.',
            'website.url' => 'La URL del sitio web no es válida.',
            'instagram_url.url' => 'La URL de Instagram no es válida.',
            'facebook_url.url' => 'La URL de Facebook no es válida.',
            'tiktok_url.url' => 'La URL de TikTok no es válida.',
            'x_url.url' => 'La URL de X/Twitter no es válida.',
        ];
    }
}
