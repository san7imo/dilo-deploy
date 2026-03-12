<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoyaltyStatementLineMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'track_id' => [
                'nullable',
                'integer',
                Rule::exists('tracks', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'track_id.exists' => 'El track seleccionado no existe o está eliminado.',
        ];
    }
}
