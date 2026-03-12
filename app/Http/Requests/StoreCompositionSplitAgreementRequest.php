<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\Compositions\CompositionSplitPoolValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;

class StoreCompositionSplitAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract' => 'required|file',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'participants' => 'required|array|min:1',
            'participants.*.role' => 'required|string|max:50',
            'participants.*.share_pool' => 'required|string|in:writer,publisher,mechanical_payee',
            'participants.*.percentage' => 'required|numeric|min:0|max:100',
            'participants.*.participant_type' => 'nullable|string|in:internal,external_existing,external_new,manual',
            'participants.*.artist_id' => 'nullable|exists:artists,id',
            'participants.*.user_id' => 'nullable|integer|exists:users,id',
            'participants.*.payee_email' => 'nullable|email',
            'participants.*.name' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'contract.required' => 'Debes subir el contrato.',
            'effective_to.after_or_equal' => 'La fecha fin debe ser mayor o igual a la fecha inicio.',
            'participants.required' => 'Debes agregar participantes.',
            'participants.*.role.required' => 'Cada participante debe tener un rol.',
            'participants.*.share_pool.required' => 'Cada participante debe tener un pool.',
            'participants.*.percentage.required' => 'Cada participante debe tener un porcentaje.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $participants = $this->input('participants', []);
            foreach ($participants as $index => $participant) {
                $artistId = !empty($participant['artist_id']) ? (int) $participant['artist_id'] : null;
                $userId = !empty($participant['user_id']) ? (int) $participant['user_id'] : null;
                $participantType = strtolower(trim((string) ($participant['participant_type'] ?? '')));
                $hasIdentity = !empty($participant['artist_id'])
                    || !empty($participant['user_id'])
                    || !empty($participant['payee_email'])
                    || !empty($participant['name']);

                if ($participantType === 'internal' && !$artistId) {
                    $validator->errors()->add(
                        "participants.$index.artist_id",
                        'Debes seleccionar un artista interno de Dilo Records.'
                    );
                }

                if ($participantType === 'external_existing' && !$userId) {
                    $validator->errors()->add(
                        "participants.$index.user_id",
                        'Debes seleccionar un artista externo existente.'
                    );
                }

                if ($participantType === 'external_new') {
                    if (empty($participant['name'])) {
                        $validator->errors()->add(
                            "participants.$index.name",
                            'Debes indicar el nombre del artista externo nuevo.'
                        );
                    }

                    if (empty($participant['payee_email'])) {
                        $validator->errors()->add(
                            "participants.$index.payee_email",
                            'Debes indicar el email para enviar la invitación.'
                        );
                    }
                }

                if (!$hasIdentity) {
                    $validator->errors()->add(
                        "participants.$index",
                        'Cada participante debe tener artista, email o nombre.'
                    );
                }

                if ($artistId && $userId) {
                    $validator->errors()->add(
                        "participants.$index",
                        'No puedes seleccionar artista interno y artista externo al mismo tiempo.'
                    );
                }
            }

            if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
                $selectedUserIds = collect($participants)
                    ->pluck('user_id')
                    ->filter()
                    ->map(fn($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($selectedUserIds->isNotEmpty()) {
                    $externalUserIds = User::query()
                        ->whereIn('id', $selectedUserIds)
                        ->whereHas('roles', function ($query) {
                            $query->where('name', 'external_artist')
                                ->where('guard_name', 'web');
                        })
                        ->pluck('id')
                        ->map(fn($id) => (int) $id)
                        ->all();

                    foreach ($participants as $index => $participant) {
                        $userId = !empty($participant['user_id']) ? (int) $participant['user_id'] : null;

                        if ($userId && !in_array($userId, $externalUserIds, true)) {
                            $validator->errors()->add(
                                "participants.$index.user_id",
                                'El usuario seleccionado no pertenece a artistas externos.'
                            );
                        }
                    }
                }
            }

            $poolErrors = app(CompositionSplitPoolValidator::class)->validate($participants);
            foreach ($poolErrors as $pool => $message) {
                $validator->errors()->add('participants', $message);
                $validator->errors()->add("participants_pool.{$pool}", $message);
            }
        });
    }
}
