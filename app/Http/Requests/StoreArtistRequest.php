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
            'name'    => ['required', 'string', 'max:255', Rule::unique('artists', 'name')->whereNull('deleted_at')],
            'legal_name' => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'password' => ['required', 'string', 'min:8'],
            'bio'     => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:50'],
            'identification_type' => [
                'nullable',
                'string',
                'required_with:identification_number',
                Rule::in(['passport', 'national_id', 'tax_id', 'residence_permit', 'driver_license', 'other']),
            ],
            'identification_number' => [
                'nullable',
                'string',
                'max:80',
                'required_with:identification_type',
                Rule::unique('users', 'identification_number')->whereNull('deleted_at'),
            ],
            'additional_information' => ['nullable', 'string', 'max:1500'],
            'genre_id' => ['nullable', 'exists:genres,id'],

            'banner_home'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'banner_artist'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'carousel_home'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'carousel_discography' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],

            // Video de presentación
            'presentation_video_url' => ['nullable', 'url'],

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
            'legal_name.required' => 'El nombre legal es obligatorio.',
            'name.unique'   => 'Ya existe un artista con este nombre.',
            'identification_type.in' => 'El tipo de documento seleccionado no es válido.',
            'identification_number.unique' => 'El número de identificación ya está registrado.',
            'social_links.*.url.url' => 'Cada enlace social debe tener una URL válida.',
            'banner_home.image' => 'El banner del home debe ser una imagen válida.',
        ];
    }
}
