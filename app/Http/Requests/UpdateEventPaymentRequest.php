<?php

namespace App\Http\Requests;

use App\Models\EventPayment;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $payment = $this->route('payment');

        return $payment instanceof EventPayment
            ? (bool) $this->user()?->can('viewFinancial', $payment->event)
            : false;
    }

    public function rules(): array
    {
        return [
            'payment_date' => ['required', 'date'],
            'amount_original' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'exchange_rate_to_base' => ['nullable', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string', 'max:255'],
            'collaborator_id' => ['nullable', 'integer', 'exists:collaborators,id'],
            'is_advance' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
            'receipt_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'El método de pago es obligatorio.',
            'payment_date.required' => 'La fecha de pago es obligatoria.',
            'amount_original.required' => 'El monto del pago es obligatorio.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $currency = strtoupper(trim((string) $this->input('currency', '')));
        $collaboratorId = $this->input('collaborator_id');

        $this->merge([
            'currency' => $currency ?: 'USD',
            'collaborator_id' => $collaboratorId === '' ? null : $collaboratorId,
        ]);
    }
}
