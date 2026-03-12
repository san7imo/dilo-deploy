<?php

namespace App\Services\Compositions;

use App\Models\Artist;
use App\Models\Composition;
use App\Models\CompositionSplitAgreement;
use App\Models\CompositionSplitSet;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompositionSplitSetService
{
    public function __construct(
        private readonly CompositionSplitPoolValidator $poolValidator
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string|null> $contractMeta
     */
    public function createVersionedSet(
        Composition $composition,
        array $payload,
        array $contractMeta,
        int $actorUserId
    ): CompositionSplitSet {
        $participants = collect($payload['participants'] ?? [])->values();
        $poolErrors = $this->poolValidator->validate($participants->all());

        if (!empty($poolErrors)) {
            throw ValidationException::withMessages([
                'participants' => array_values($poolErrors),
            ]);
        }

        return DB::transaction(function () use ($composition, $payload, $participants, $contractMeta, $actorUserId): CompositionSplitSet {
            CompositionSplitSet::query()
                ->where('composition_id', $composition->id)
                ->where('status', 'active')
                ->update(['status' => 'archived']);

            CompositionSplitAgreement::query()
                ->where('composition_id', $composition->id)
                ->where('status', 'active')
                ->update(['status' => 'archived']);

            $nextVersion = ((int) CompositionSplitSet::query()
                ->where('composition_id', $composition->id)
                ->max('version')) + 1;

            $set = CompositionSplitSet::query()->create([
                'composition_id' => $composition->id,
                'version' => $nextVersion,
                'status' => 'active',
                'effective_from' => $payload['effective_from'] ?? null,
                'effective_to' => $payload['effective_to'] ?? null,
                'contract_path' => $contractMeta['path'] ?? null,
                'contract_original_filename' => $contractMeta['original_filename'] ?? null,
                'contract_hash' => $contractMeta['hash'] ?? null,
                'created_by' => $actorUserId,
            ]);

            // Compatibilidad temporal: mantener acuerdo legacy para allocations pre-Hito 9.
            $legacyAgreement = CompositionSplitAgreement::query()->create([
                'composition_id' => $composition->id,
                'status' => 'active',
                'effective_from' => $payload['effective_from'] ?? null,
                'effective_to' => $payload['effective_to'] ?? null,
                'contract_path' => $contractMeta['path'] ?? null,
                'contract_original_filename' => $contractMeta['original_filename'] ?? null,
                'contract_hash' => $contractMeta['hash'] ?? null,
                'created_by' => $actorUserId,
            ]);

            $resolvedParticipants = $this->resolveParticipants(
                $participants,
                $set->id,
                $legacyAgreement->id
            );

            $set->participants()->createMany($resolvedParticipants);

            return $set->fresh(['participants']);
        });
    }

    /**
     * @param Collection<int, array<string, mixed>> $participants
     * @return array<int, array<string, mixed>>
     */
    private function resolveParticipants(
        Collection $participants,
        int $splitSetId,
        int $legacyAgreementId
    ): array {
        $artistIds = $participants
            ->pluck('artist_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $artistUserMap = Artist::query()
            ->whereIn('id', $artistIds)
            ->pluck('user_id', 'id')
            ->map(fn($value) => $value ? (int) $value : null);

        $participantEmails = $participants
            ->pluck('payee_email')
            ->filter()
            ->map(fn($email) => strtolower(trim((string) $email)))
            ->unique()
            ->values();

        $selectedUserIds = $participants
            ->pluck('user_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $selectedUsers = User::query()
            ->whereIn('id', $selectedUserIds)
            ->get(['id', 'name', 'stage_name', 'email'])
            ->keyBy('id');

        $usersByEmail = User::query()
            ->whereIn('email', $participantEmails)
            ->get(['id', 'email'])
            ->mapWithKeys(fn(User $user) => [strtolower($user->email) => (int) $user->id]);

        return $participants->map(function (array $participant) use (
            $splitSetId,
            $legacyAgreementId,
            $artistUserMap,
            $selectedUsers,
            $usersByEmail
        ): array {
            $email = !empty($participant['payee_email'])
                ? strtolower(trim((string) $participant['payee_email']))
                : null;

            $artistId = !empty($participant['artist_id'])
                ? (int) $participant['artist_id']
                : null;

            $selectedUserId = !empty($participant['user_id'])
                ? (int) $participant['user_id']
                : null;

            $userId = $selectedUserId ?: ($artistId ? ($artistUserMap[$artistId] ?? null) : null);
            if ($userId && $selectedUsers->has($userId)) {
                $selectedUser = $selectedUsers->get($userId);
                $selectedUserEmail = strtolower(trim((string) ($selectedUser->email ?? '')));
                if (!$email && $selectedUserEmail !== '') {
                    $email = $selectedUserEmail;
                }

                if (empty($participant['name'])) {
                    $participant['name'] = $selectedUser->stage_name ?: $selectedUser->name;
                }
            } elseif (!$userId && $email) {
                $userId = $usersByEmail[$email] ?? null;
            }

            if ($email !== null && trim($email) === '') {
                $email = null;
            }

            return [
                'composition_split_set_id' => $splitSetId,
                'composition_split_agreement_id' => $legacyAgreementId,
                'artist_id' => $artistId,
                'user_id' => $userId,
                'payee_email' => $email,
                'name' => $participant['name'] ?? null,
                'role' => strtolower(trim((string) ($participant['role'] ?? 'writer'))),
                'share_pool' => $this->normalizePool($participant['share_pool'] ?? null),
                'percentage' => $participant['percentage'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();
    }

    private function normalizePool(mixed $value): string
    {
        $pool = strtolower(trim((string) $value));

        return in_array($pool, CompositionSplitPoolValidator::REQUIRED_POOLS, true)
            ? $pool
            : 'writer';
    }
}

