<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // importante: la key del route model binding es 'release' (minúscula)
        $release = $this->route('release');

        return [
            'artist_id'    => 'sometimes|exists:artists,id',
            'title'        => [
                'sometimes','required','string','max:255',
                Rule::unique('releases','title')->ignore($release?->id)
            ],
            'release_date' => 'sometimes|nullable|date',
            'type'         => 'sometimes|nullable|string|in:single,ep,album,mixtape,live,compilation',
            'description'  => 'sometimes|nullable|string',

            // 👇 Alineado con el form (cover_file)
            'cover_file'   => 'sometimes|nullable|file|mimes:jpeg,png,webp|max:4096',

            // URLs
            'spotify_url'      => 'sometimes|nullable|url',
            'youtube_url'      => 'sometimes|nullable|url',
            'apple_music_url'  => 'sometimes|nullable|url',
            'deezer_url'       => 'sometimes|nullable|url',
            'amazon_music_url' => 'sometimes|nullable|url',
            'soundcloud_url'   => 'sometimes|nullable|url',
            'tidal_url'        => 'sometimes|nullable|url',
        ];
    }

    public function messages(): array
    {
        return [
            'artist_id.exists'  => 'El artista seleccionado no existe.',
            'title.required'    => 'El título del lanzamiento es obligatorio.',
            'title.unique'      => 'Ya existe un lanzamiento con este título.',
            'type.in'           => 'El tipo debe ser single, ep, album, mixtape, live o compilation.',
            'cover_file.file'   => 'La portada debe ser un archivo válido.',
            'cover_file.mimes'  => 'La portada debe ser JPEG, PNG o WebP.',
            'cover_file.max'    => 'La portada no puede superar 4MB.',
        ];
    }
}
