<?php

namespace App\Services\Compositions;

use App\Models\Composition;
use App\Models\CompositionRegistration;
use App\Models\RecordingComposition;
use Illuminate\Support\Facades\DB;

class CompositionCatalogService
{
    public function create(array $payload, ?int $actorUserId = null): Composition
    {
        return DB::transaction(function () use ($payload, $actorUserId): Composition {
            $trackIds = collect($payload['track_ids'] ?? [])
                ->filter()
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            unset($payload['track_ids']);
            $payload['iswc'] = $this->normalizeIswc($payload['iswc'] ?? null);

            $composition = Composition::query()->create($payload);

            $this->syncRecordingLinks($composition, $trackIds);
            $this->syncIswcRegistration($composition, $actorUserId);

            return $composition->fresh(['tracks:id,title,isrc', 'registrations']);
        });
    }

    public function update(Composition $composition, array $payload, ?int $actorUserId = null): Composition
    {
        return DB::transaction(function () use ($composition, $payload, $actorUserId): Composition {
            $trackIds = collect($payload['track_ids'] ?? [])
                ->filter()
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            unset($payload['track_ids']);
            $payload['iswc'] = $this->normalizeIswc($payload['iswc'] ?? null);

            $composition->update($payload);

            $this->syncRecordingLinks($composition, $trackIds);
            $this->syncIswcRegistration($composition->fresh(), $actorUserId);

            return $composition->fresh(['tracks:id,title,isrc', 'registrations']);
        });
    }

    private function syncRecordingLinks(Composition $composition, array $trackIds): void
    {
        $existingTrackIds = RecordingComposition::query()
            ->where('composition_id', $composition->id)
            ->pluck('track_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $toRemove = array_values(array_diff($existingTrackIds, $trackIds));
        if (!empty($toRemove)) {
            RecordingComposition::query()
                ->where('composition_id', $composition->id)
                ->whereIn('track_id', $toRemove)
                ->get()
                ->each
                ->delete();
        }

        $toAdd = array_values(array_diff($trackIds, $existingTrackIds));
        foreach ($toAdd as $trackId) {
            RecordingComposition::query()->create([
                'composition_id' => $composition->id,
                'track_id' => (int) $trackId,
                'relationship_type' => 'main_work',
                'source' => 'composition_catalog_service',
            ]);
        }
    }

    private function syncIswcRegistration(Composition $composition, ?int $actorUserId = null): void
    {
        $iswc = $this->normalizeIswc($composition->iswc);

        if ($iswc === null) {
            CompositionRegistration::query()
                ->where('composition_id', $composition->id)
                ->where('registration_type', 'iswc')
                ->get()
                ->each
                ->delete();

            return;
        }

        $existing = CompositionRegistration::query()
            ->where('composition_id', $composition->id)
            ->where('registration_type', 'iswc')
            ->orderBy('id')
            ->first();

        if ($existing) {
            $existing->update([
                'registration_number' => $iswc,
                'status' => 'active',
            ]);

            CompositionRegistration::query()
                ->where('composition_id', $composition->id)
                ->where('registration_type', 'iswc')
                ->where('id', '!=', $existing->id)
                ->get()
                ->each
                ->delete();

            return;
        }

        CompositionRegistration::query()->create([
            'composition_id' => $composition->id,
            'registration_type' => 'iswc',
            'society_name' => null,
            'registration_number' => $iswc,
            'territory_code' => null,
            'status' => 'active',
            'metadata' => [
                'source' => 'composition.iswc',
            ],
            'created_by' => $actorUserId,
        ]);
    }

    private function normalizeIswc(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(trim((string) $value));
        $normalized = preg_replace('/\s+/', '', $normalized);

        return $normalized !== '' ? $normalized : null;
    }
}
