<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoyaltyStatementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => 'required|string|in:symphonic',
            'file' => 'required|file|mimes:csv,txt',
        ];
    }

    public function messages(): array
    {
        return [
            'provider.required' => 'El proveedor es obligatorio.',
            'provider.in' => 'Proveedor no soportado.',
            'file.required' => 'Debes seleccionar un archivo CSV.',
            'file.mimes' => 'El archivo debe ser un CSV válido.',
        ];
    }
}
