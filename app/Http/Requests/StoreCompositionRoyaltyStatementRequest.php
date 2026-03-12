<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompositionRoyaltyStatementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => 'required|string|in:manual_dilo',
            'reporting_period' => 'nullable|string|max:30',
            'source_name' => 'nullable|string|max:255',
            'file' => 'required|file|mimes:csv,txt',
        ];
    }

    public function messages(): array
    {
        return [
            'provider.in' => 'Proveedor no soportado para composición.',
            'file.required' => 'Debes subir el archivo CSV de composición.',
            'file.mimes' => 'El archivo debe ser CSV.',
        ];
    }
}

