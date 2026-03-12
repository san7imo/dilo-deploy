<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'iswc' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'track_ids' => ['nullable', 'array'],
            'track_ids.*' => ['integer', 'exists:tracks,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título de la composición es obligatorio.',
            'track_ids.*.exists' => 'Uno o más tracks seleccionados no existen.',
        ];
    }
}
