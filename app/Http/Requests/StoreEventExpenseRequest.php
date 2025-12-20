<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventExpenseRequest extends FormRequest
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
            'expense_date' => ['nullable', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:100'],
            'amount_original' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'exchange_rate_to_base' => ['nullable', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del gasto es obligatorio.',
            'amount_original.required' => 'El monto del gasto es obligatorio.',
            'expense_date.date' => 'La fecha del gasto no es válida.',
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
