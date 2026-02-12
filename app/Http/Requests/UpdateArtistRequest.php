<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArtistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $artist = $this->route('artist');
        $userId = $artist?->user_id;

        return [
            'name'    => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('artists', 'name')->ignore($this->route('artist')->id),
            ],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'bio'     => ['sometimes', 'nullable', 'string'],
            'country' => ['sometimes', 'nullable', 'string', 'max:120'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'genre_id' => ['sometimes', 'nullable', 'exists:genres,id'],

            'banner_home'          => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
            'banner_artist'        => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
            'carousel_home'        => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
            'carousel_discography' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,webp', 'max:4096'],

            // Video de presentación
            'presentation_video_url' => ['sometimes', 'nullable', 'url'],

            // social_links es un objeto con claves de plataformas
            'social_links'           => ['sometimes', 'nullable', 'array'],
            'social_links.spotify'   => ['sometimes', 'nullable', 'url'],
            'social_links.youtube'   => ['sometimes', 'nullable', 'url'],
            'social_links.instagram' => ['sometimes', 'nullable', 'url'],
            'social_links.tiktok'    => ['sometimes', 'nullable', 'url'],
            'social_links.facebook'  => ['sometimes', 'nullable', 'url'],
            'social_links.x'         => ['sometimes', 'nullable', 'url'],
            'social_links.apple'     => ['sometimes', 'nullable', 'url'],
            'social_links.amazon'    => ['sometimes', 'nullable', 'url'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del artista es obligatorio.',
            'name.unique'   => 'Ya existe un artista con este nombre.',
            'email.required' => 'El correo es obligatorio.',
            'email.unique' => 'El correo ya esta en uso.',
            'password.min' => 'La contrasena debe tener al menos 8 caracteres.',
            'social_links.*.url.url' => 'Cada enlace social debe tener una URL válida.',
        ];
    }
}
