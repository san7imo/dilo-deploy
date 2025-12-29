<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class SyncEventExpensesRequest extends FormRequest
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
            'expenses' => ['required', 'array'],
            'expenses.*.id' => ['nullable', 'integer'],
            'expenses.*.name' => ['required', 'string', 'max:255'],
            'expenses.*.description' => ['nullable', 'string', 'max:500'],
            'expenses.*.category' => ['nullable', 'string', 'max:100'],
            'expenses.*.expense_date' => ['nullable', 'date'],
            'expenses.*.amount_original' => ['required', 'numeric', 'min:0'],
            'expenses.*.currency' => ['required', 'string', 'size:3'],
            'expenses.*.exchange_rate_to_base' => ['nullable', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'expenses.required' => 'Los gastos son obligatorios.',
            'expenses.array' => 'Los gastos deben ser un arreglo.',
            'expenses.*.name.required' => 'El nombre del gasto es obligatorio.',
            'expenses.*.amount_original.required' => 'El monto del gasto es obligatorio.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $expenses = $this->input('expenses', []);

        if (is_array($expenses)) {
            foreach ($expenses as &$expense) {
                if (isset($expense['currency'])) {
                    $expense['currency'] = strtoupper(trim((string) $expense['currency']));
                    if (empty($expense['currency'])) {
                        $expense['currency'] = 'USD';
                    }
                }
            }
            $this->merge(['expenses' => $expenses]);
        }
    }
}
