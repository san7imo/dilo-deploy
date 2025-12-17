<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event
            ? (bool) $this->user()?->can('viewFinancial', $event)
            : false;
    }

    public function rules(): array
    {
        return [
            'payment_date' => ['required', 'date'],
            'amount_original' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'exchange_rate_to_base' => ['nullable', 'numeric', 'gt:0'],
            'payment_method' => ['nullable', 'string'],
            'is_advance' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $currency = strtoupper(trim((string) $this->input('currency', '')));

        $this->merge([
            'currency' => $currency ?: 'EUR',
        ]);
    }
}
