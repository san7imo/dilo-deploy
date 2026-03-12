<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'concept' => ['required', 'string', 'max:180'],
            'payment_method' => ['nullable', 'string', 'max:80'],
            'payment_date' => ['required', 'date'],
            'amount_usd' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:2000'],
            'receipt_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'concept.required' => 'El concepto del pago es obligatorio.',
            'payment_date.required' => 'La fecha del pago es obligatoria.',
            'amount_usd.required' => 'El monto en USD es obligatorio.',
            'amount_usd.min' => 'El monto debe ser mayor a cero.',
            'receipt_file.mimes' => 'El soporte debe ser una imagen (JPG, PNG, WEBP) o PDF.',
            'receipt_file.max' => 'El soporte no puede superar los 10 MB.',
        ];
    }
}
