<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResendExternalArtistInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $artist = $this->route('artist');

        return [
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($artist?->user_id)
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Debes ingresar el correo del artista externo.',
            'email.email' => 'Debes ingresar un correo válido.',
            'email.unique' => 'El correo ya está registrado en otra cuenta activa.',
        ];
    }
}
