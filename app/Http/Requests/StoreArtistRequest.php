<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArtistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:255', Rule::unique('artists', 'name')],
            'email'   => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'bio'     => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'max:120'],
            'genre_id' => ['nullable', 'exists:genres,id'],

            'banner_home'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'banner_artist'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'carousel_home'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'carousel_discography' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],

            // social_links es un objeto con claves de plataformas
            'social_links'           => ['nullable', 'array'],
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
            'banner_home.image' => 'El banner del home debe ser una imagen válida.',
        ];
    }
}
