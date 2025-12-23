<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'event_date'  => 'required|date|after_or_equal:today',
            'event_type'  => 'sometimes|nullable|string|max:100',
            'country'     => 'sometimes|nullable|string|max:100',
            'city'        => 'sometimes|nullable|string|max:100',
            'venue_address' => 'sometimes|nullable|string|max:255',
            'status'      => 'sometimes|nullable|string|max:100',
            'show_fee_total' => 'sometimes|nullable|numeric|min:0',
            'currency'    => 'sometimes|nullable|string|size:3',
            'advance_percentage' => 'sometimes|nullable|numeric|min:0|max:100',
            'advance_expected' => 'sometimes|boolean',
            'full_payment_due_date' => 'sometimes|nullable|date',

            // Imagen del póster (archivo)
            'poster_file'      => 'sometimes|nullable|image|mimes:jpeg,png,webp|max:4096',

            // Asociación con artistas
            'artist_ids'   => 'required|array|min:1',
            'artist_ids.*' => 'integer|exists:artists,id',
            'main_artist_id' => [
                'required',
                'integer',
                'exists:artists,id',
                Rule::in($this->input('artist_ids', [])),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'        => 'El título del evento es obligatorio.',
            'poster.image'          => 'El póster debe ser una imagen válida (JPEG, PNG o WebP).',
            'poster.max'            => 'El tamaño máximo permitido para el póster es de 4MB.',
            'artist_ids.*.exists'   => 'Uno o más artistas seleccionados no existen en el sistema.',
            'main_artist_id.required' => 'Debes seleccionar un artista principal.',
            'main_artist_id.in'     => 'El artista principal debe estar en la lista de artistas.',
            'event_date.required'   => 'La fecha del evento es obligatoria.',
            'event_date.after_or_equal' => 'La fecha del evento debe ser futura.',
        ];
    }
}
