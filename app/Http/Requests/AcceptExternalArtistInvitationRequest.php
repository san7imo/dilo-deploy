<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcceptExternalArtistInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'stage_name' => ['required', 'string', 'max:255'],
            'identification_type' => ['required', 'string', Rule::in([
                'passport',
                'national_id',
                'tax_id',
                'residence_permit',
                'driver_license',
                'other',
            ])],
            'identification_number' => [
                'required',
                'string',
                'max:80',
                Rule::unique('users', 'identification_number')->whereNull('deleted_at'),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'additional_information' => ['nullable', 'string', 'max:1500'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre legal es obligatorio.',
            'stage_name.required' => 'El nombre artístico es obligatorio.',
            'identification_type.required' => 'El tipo de documento es obligatorio.',
            'identification_type.in' => 'El tipo de documento seleccionado no es válido.',
            'identification_number.required' => 'El número de identificación es obligatorio.',
            'identification_number.unique' => 'El número de identificación ya está registrado.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ];
    }
}
