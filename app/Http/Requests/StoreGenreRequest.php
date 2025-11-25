<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGenreRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta acción.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para crear un género.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('genres', 'name'),
            ],
        ];
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del género es obligatorio.',
            'name.string'   => 'El nombre del género debe ser un texto válido.',
            'name.max'      => 'El nombre del género no puede superar los 255 caracteres.',
            'name.unique'   => 'Ya existe un género con este nombre.',
        ];
    }
}
