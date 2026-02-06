<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // más adelante se puede restringir según roles o permisos
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('isrc')) {
            $this->merge([
                'isrc' => $this->normalizeIsrc($this->input('isrc')),
            ]);
        }
    }

    public function rules(): array
    {
        $track = $this->route('track');

        return [
            // Relaciones
            'release_id'   => 'sometimes|nullable|exists:releases,id',

            // Datos principales
            'title'        => [
                'sometimes','required','string','max:255',
                Rule::unique('tracks', 'title')->ignore($track?->id)
            ],
            'isrc'         => 'sometimes|nullable|string|max:32',
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

    private function normalizeIsrc($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $value));
        return $normalized !== '' ? $normalized : null;
    }
}
