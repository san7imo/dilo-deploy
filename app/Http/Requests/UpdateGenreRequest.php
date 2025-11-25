<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGenreRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta acción.
     */
    public function authorize(): bool
    {
        // Puedes reemplazar esto luego con una política o verificación de roles
        return true;
    }

    /**
     * Reglas de validación para actualizar un género.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes', // solo valida si viene en la request
                'required',
                'string',
                'max:255',
                Rule::unique('genres', 'name')->ignore($this->route('genre')->id),
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
