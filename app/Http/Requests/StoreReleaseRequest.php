<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('upc')) {
            $this->merge([
                'upc' => $this->normalizeUpc($this->input('upc')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'artist_id'    => 'required|exists:artists,id',
            'title'        => 'required|string|max:255|unique:releases,title',
            'upc'          => 'nullable|string|max:32',
            'release_date' => 'nullable|date',
            'type'         => 'nullable|string|in:single,ep,album,mixtape,live,compilation',
            'description'  => 'nullable|string',

            // 👇 Alineado con el form (cover_file)
            'cover_file'   => 'nullable|file|mimes:jpeg,png,webp|max:4096',

            // URLs plataformas
            'spotify_url'      => 'nullable|url',
            'youtube_url'      => 'nullable|url',
            'apple_music_url'  => 'nullable|url',
            'deezer_url'       => 'nullable|url',
            'amazon_music_url' => 'nullable|url',
            'soundcloud_url'   => 'nullable|url',
            'tidal_url'        => 'nullable|url',
        ];
    }

    public function messages(): array
    {
        return [
            'artist_id.required' => 'El lanzamiento debe estar asociado a un artista.',
            'artist_id.exists'   => 'El artista seleccionado no existe.',
            'title.required'     => 'El título del lanzamiento es obligatorio.',
            'title.unique'       => 'Ya existe un lanzamiento con este título.',
            'type.in'            => 'El tipo debe ser single, ep, album, mixtape, live o compilation.',
            'cover_file.file'    => 'La portada debe ser un archivo válido.',
            'cover_file.mimes'   => 'La portada debe ser JPEG, PNG o WebP.',
            'cover_file.max'     => 'La portada no puede superar 4MB.',
        ];
    }

    private function normalizeUpc($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $normalized = preg_replace('/\D/', '', (string) $value);
        return $normalized !== '' ? $normalized : null;
    }
}
