<?php

namespace App\Services;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArtistCatalogService
{
    public function syncExternalUsersWithoutArtist(): Collection
    {
        return User::query()
            ->role('external_artist')
            ->whereDoesntHave('artist')
            ->whereNotIn('id', Artist::withTrashed()
                ->whereNotNull('user_id')
                ->select('user_id'))
            ->orderBy('id')
            ->get()
            ->map(fn (User $user) => $this->createOrAttachExternalArtist(
                displayName: $this->resolveDisplayName($user->stage_name, $user->name),
                user: $user
            ));
    }

    public function createOrAttachExternalArtist(
        string $displayName,
        ?User $user = null,
        ?Artist $artist = null
    ): Artist {
        return DB::transaction(function () use ($displayName, $user, $artist): Artist {
            $artist = $artist?->exists ? $artist->fresh() : $artist;

            if (!$artist && $user) {
                $artist = Artist::query()
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();
            }

            if (!$artist) {
                $artist = Artist::query()->create([
                    'name' => $this->buildUniqueArtistName($displayName),
                    'slug' => $this->buildUniqueSlug($displayName),
                    'user_id' => $user?->id,
                    'phone' => $user?->phone,
                    'artist_origin' => 'external',
                    'has_public_profile' => false,
                ]);

                return $artist;
            }

            $updates = [
                'artist_origin' => 'external',
                'has_public_profile' => false,
            ];

            if ($user && $artist->user_id !== $user->id) {
                $updates['user_id'] = $user->id;
            }

            if ($user && empty($artist->phone) && !empty($user->phone)) {
                $updates['phone'] = $user->phone;
            }

            $normalizedDisplayName = trim($displayName);
            if ($normalizedDisplayName !== '' && $artist->name !== $normalizedDisplayName) {
                $updates['name'] = $this->buildUniqueArtistName($normalizedDisplayName, $artist->id);
                $updates['slug'] = $this->buildUniqueSlug($normalizedDisplayName, $artist->id);
            }

            $artist->update($updates);

            return $artist->fresh();
        });
    }

    public function convertToExternal(Artist $artist): Artist
    {
        return DB::transaction(function () use ($artist): Artist {
            $artist->update([
                'artist_origin' => 'external',
                'has_public_profile' => false,
            ]);

            $user = $artist->user;
            if ($user) {
                if ($user->hasRole('artist')) {
                    $user->removeRole('artist');
                }

                if (!$user->hasRole('external_artist')) {
                    $user->assignRole('external_artist');
                }
            }

            $this->flushArtistCaches();

            return $artist->fresh(['user']);
        });
    }

    public function convertToInternal(Artist $artist): Artist
    {
        return DB::transaction(function () use ($artist): Artist {
            $artist->update([
                'artist_origin' => 'internal',
                'has_public_profile' => true,
            ]);

            $user = $artist->user;
            if ($user) {
                if ($user->hasRole('external_artist')) {
                    $user->removeRole('external_artist');
                }

                if (!$user->hasRole('artist')) {
                    $user->assignRole('artist');
                }
            }

            $this->flushArtistCaches();

            return $artist->fresh(['user']);
        });
    }

    public function resolveDisplayName(?string ...$candidates): string
    {
        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return 'artista-externo';
    }

    private function buildUniqueArtistName(string $baseName, ?int $ignoreArtistId = null): string
    {
        $baseName = trim($baseName) !== '' ? trim($baseName) : 'artista-externo';
        $name = $baseName;
        $suffix = 2;

        while ($this->artistNameExists($name, $ignoreArtistId)) {
            $name = "{$baseName} ({$suffix})";
            $suffix++;
        }

        return $name;
    }

    private function buildUniqueSlug(string $baseName, ?int $ignoreArtistId = null): string
    {
        $baseSlug = Str::slug($baseName);
        if ($baseSlug === '') {
            $baseSlug = 'artista-externo';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while ($this->artistSlugExists($slug, $ignoreArtistId)) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function artistNameExists(string $name, ?int $ignoreArtistId = null): bool
    {
        return Artist::withTrashed()
            ->when($ignoreArtistId, fn ($query) => $query->where('id', '!=', $ignoreArtistId))
            ->where('name', $name)
            ->exists();
    }

    private function artistSlugExists(string $slug, ?int $ignoreArtistId = null): bool
    {
        return Artist::withTrashed()
            ->when($ignoreArtistId, fn ($query) => $query->where('id', '!=', $ignoreArtistId))
            ->where('slug', $slug)
            ->exists();
    }

    private function flushArtistCaches(): void
    {
        Cache::flush();
    }
}
