<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'location'    => 'nullable|string|max:255',
            'event_date'  => 'required|date',
            'event_type'  => 'nullable|string|max:100',
            'country'     => 'nullable|string|max:100',
            'city'        => 'nullable|string|max:100',
            'venue_address' => 'nullable|string|max:255',
            'whatsapp_event' => 'nullable|string|max:255',
            'page_tickets' => 'nullable|string|max:255',
            'status'      => 'nullable|string|max:100',
            'show_fee_total' => 'nullable|numeric|min:0',
            'currency'    => 'nullable|string|size:3',
            'advance_percentage' => 'nullable|numeric|min:0|max:100',
            'artist_share_percentage' => 'nullable|numeric|min:0|max:100',
            'label_share_percentage' => 'nullable|numeric|min:0|max:100',
            'advance_expected' => 'boolean',
            'full_payment_due_date' => 'nullable|date',

            // Imagen del póster (archivo)
            'poster_file'      => 'nullable|image|mimes:jpeg,png,webp|max:4096',

            // Asociación con artistas
            'artist_ids'   => 'required|array|min:1',
            'artist_ids.*' => 'integer|exists:artists,id',
            'main_artist_id' => [
                'required',
                'integer',
                'exists:artists,id',
                Rule::in($this->input('artist_ids', [])),
            ],

            // Road managers asignados
            'road_manager_ids' => 'nullable|array',
            'road_manager_ids.*' => 'integer|exists:users,id',
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
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $artist = $this->input('artist_share_percentage');
            $label = $this->input('label_share_percentage');

            if (is_null($artist) && is_null($label)) {
                return;
            }

            if (is_null($artist) || is_null($label)) {
                $validator->errors()->add(
                    'artist_share_percentage',
                    'Debes indicar el porcentaje del artista y de la disquera.'
                );
                return;
            }

            $sum = round(((float) $artist) + ((float) $label), 2);
            if (abs($sum - 100) > 0.01) {
                $validator->errors()->add(
                    'artist_share_percentage',
                    'La suma de porcentajes del artista y la disquera debe ser 100%.'
                );
            }
        });
    }
}
