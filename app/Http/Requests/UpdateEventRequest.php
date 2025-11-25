<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'location'    => 'sometimes|nullable|string|max:255',
            'event_date'  => 'sometimes|nullable|date',

            // Imagen del póster (archivo)
            'poster'      => 'sometimes|nullable|image|mimes:jpeg,png,webp|max:4096',

            // Asociación con artistas
            'artist_ids'   => 'sometimes|nullable|array',
            'artist_ids.*' => 'integer|exists:artists,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'        => 'El título del evento es obligatorio.',
            'poster.image'          => 'El póster debe ser una imagen válida (JPEG, PNG o WebP).',
            'poster.max'            => 'El tamaño máximo permitido para el póster es de 4MB.',
            'artist_ids.*.exists'   => 'Uno o más artistas seleccionados no existen en el sistema.',
        ];
    }
}
