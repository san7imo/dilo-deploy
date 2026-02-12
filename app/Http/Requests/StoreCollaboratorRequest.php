<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCollaboratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_holder' => ['required', 'string', 'max:255'],
            'holder_id' => ['required', 'string', 'max:255'],
            'account_type' => ['required', 'string', 'max:100'],
            'bank' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'account_number' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'account_holder.required' => 'El titular de la cuenta es obligatorio.',
            'holder_id.required' => 'El ID del titular es obligatorio.',
            'account_type.required' => 'El tipo de cuenta es obligatorio.',
            'bank.required' => 'El banco es obligatorio.',
            'address.required' => 'La dirección es obligatoria.',
            'account_number.required' => 'El número de cuenta es obligatorio.',
            'country.required' => 'El país es obligatorio.',
        ];
    }
}
