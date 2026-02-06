<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrackSplitAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'split_type' => 'required|string|in:master',
            'contract' => 'required|file',
            'participants' => 'required|array|min:1',
            'participants.*.role' => 'required|string|max:50',
            'participants.*.percentage' => 'required|numeric|min:0|max:100',
            'participants.*.artist_id' => 'nullable|exists:artists,id',
            'participants.*.payee_email' => 'nullable|email',
            'participants.*.name' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'contract.required' => 'Debes subir el contrato.',
            'participants.required' => 'Debes agregar participantes.',
            'participants.*.role.required' => 'Cada participante debe tener un rol.',
            'participants.*.percentage.required' => 'Cada participante debe tener un porcentaje.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $participants = $this->input('participants', []);

            $sum = 0.0;
            $hasLabel = false;
            foreach ($participants as $index => $participant) {
                $sum += (float) ($participant['percentage'] ?? 0);
                $role = strtolower(trim((string) ($participant['role'] ?? '')));
                if ($role === 'label') {
                    $hasLabel = true;
                }

                $hasIdentity = !empty($participant['artist_id'])
                    || !empty($participant['payee_email'])
                    || !empty($participant['name']);

                if (!$hasIdentity) {
                    $validator->errors()->add(
                        "participants.$index",
                        'Cada participante debe tener artista, email o nombre.'
                    );
                }
            }

            if (abs($sum - 100) > 0.01) {
                $validator->errors()->add('participants', 'La suma de porcentajes debe ser 100.');
            }

            if (!$hasLabel) {
                $validator->errors()->add('participants', 'Debe existir un participante con rol label.');
            }
        });
    }
}
