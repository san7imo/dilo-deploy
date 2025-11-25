<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // más adelante se puede restringir según roles o permisos
    }

    public function rules(): array
    {
        return [
            // Relaciones
            'release_id'   => 'sometimes|nullable|exists:releases,id',

            // Datos principales
            'title'        => 'sometimes|required|string|max:255|unique:tracks,title,' . $this->route('track'),
            'track_number' => 'sometimes|nullable|integer|min:1',
            'duration'     => 'sometimes|nullable|string|max:10',

            // Imagen de portada (archivo opcional)
            'cover'        => 'sometimes|nullable|image|mimes:jpeg,png,webp|max:4096',

            // Previsualización de audio (URL)
            'preview_url'  => 'sometimes|nullable|url',

            // Plataformas de streaming
            'spotify_url'      => 'sometimes|nullable|url',
            'youtube_url'      => 'sometimes|nullable|url',
            'apple_music_url'  => 'sometimes|nullable|url',
            'deezer_url'       => 'sometimes|nullable|url',
            'amazon_music_url' => 'sometimes|nullable|url',
            'soundcloud_url'   => 'sometimes|nullable|url',
            'tidal_url'        => 'sometimes|nullable|url',

            // Artistas asociados (para colaboraciones)
            'artist_ids'   => 'sometimes|nullable|array',
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
