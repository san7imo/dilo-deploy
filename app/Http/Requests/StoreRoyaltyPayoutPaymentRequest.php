<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoyaltyPayoutPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount_usd' => ['required', 'numeric', 'min:0.000001'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'paid_at' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
