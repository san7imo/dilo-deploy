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
        return [
            'name'    => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('artists', 'name')->ignore($this->route('artist')->id),
            ],
            'bio'     => ['sometimes', 'nullable', 'string'],
            'country' => ['sometimes', 'nullable', 'string', 'max:120'],
            'genre_id' => ['sometimes', 'nullable', 'exists:genres,id'],

            'banner_home'          => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
            'banner_artist'        => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
            'carousel_home'        => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
            'carousel_discography' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,webp', 'max:4096'],

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
            'social_links.*.url.url' => 'Cada enlace social debe tener una URL válida.',
        ];
    }
}
