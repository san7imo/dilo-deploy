<?php

namespace App\Http\Requests;

use App\Models\ArtistEventExpense;
use Illuminate\Foundation\Http\FormRequest;

class UpdateArtistEventExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $expense = $this->route('artistExpense');
        $user = $this->user();

        if (!$expense instanceof ArtistEventExpense || !$user) {
            return false;
        }

        // Admin puede editar cualquier gasto
        if ($user->hasRole('admin')) {
            return true;
        }

        // Road manager puede editar gastos del evento que maneja
        if ($user->hasRole('roadmanager')) {
            return $expense->event->roadManagers()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'category' => ['required', 'string', 'max:100'],
            'expense_date' => ['required', 'date'],
            'amount_original' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'exchange_rate_to_base' => ['nullable', 'numeric', 'gt:0'],
            'receipt_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del gasto es obligatorio.',
            'category.required' => 'La categoría del gasto es obligatoria.',
            'expense_date.required' => 'La fecha del gasto es obligatoria.',
            'amount_original.required' => 'El monto del gasto es obligatorio.',
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
