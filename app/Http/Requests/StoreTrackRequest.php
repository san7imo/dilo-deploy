<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Relaciones
            'release_id'   => 'nullable|exists:releases,id',

            // Datos principales
            'title'        => 'required|string|max:255|unique:tracks,title',
            'track_number' => 'nullable|integer|min:1',
            'duration'     => 'nullable|string|max:10',

            // Imagen de portada de la pista (archivo)
            'cover'        => 'nullable|image|mimes:jpeg,png,webp|max:4096',

            // Previsualización de audio (solo URL)
            'preview_url'  => 'nullable|url',

            // Plataformas de streaming
            'spotify_url'      => 'nullable|url',
            'youtube_url'      => 'nullable|url',
            'apple_music_url'  => 'nullable|url',
            'deezer_url'       => 'nullable|url',
            'amazon_music_url' => 'nullable|url',
            'soundcloud_url'   => 'nullable|url',
            'tidal_url'        => 'nullable|url',

            // Artistas asociados (para colaboraciones)
            'artist_ids'   => 'nullable|array',
            'artist_ids.*' => 'integer|exists:artists,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'        => 'El título de la pista es obligatorio.',
            'title.unique'          => 'Ya existe una pista con este título.',
            'cover.image'           => 'La portada debe ser una imagen válida (JPEG, PNG o WebP).',
            'cover.max'             => 'La portada no debe superar los 4 MB de tamaño.',
            'artist_ids.*.exists'   => 'Uno o más artistas asociados no existen en el sistema.',
        ];
    }
}
