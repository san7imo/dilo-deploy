<?php

namespace App\Http\Requests;

use App\Models\EventPersonalExpense;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventPersonalExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $expense = $this->route('personalExpense');

        return $expense instanceof EventPersonalExpense
            ? (bool) $this->user()?->can('viewFinancial', $expense->event)
            : false;
    }

    public function rules(): array
    {
        return [
            'expense_date' => ['nullable', 'date'],
            'expense_type' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'string', 'max:100'],
            'recipient' => ['nullable', 'string', 'max:255'],
            'amount_original' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'exchange_rate_to_base' => ['nullable', 'numeric', 'gt:0'],
            'receipt_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del gasto es obligatorio.',
            'payment_method.required' => 'El método de gasto es obligatorio.',
            'amount_original.required' => 'El monto del gasto es obligatorio.',
            'expense_date.date' => 'La fecha del gasto no es válida.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $currency = strtoupper(trim((string) $this->input('currency', '')));

        $this->merge([
            'currency' => $currency ?: 'USD',
        ]);
    }
}
