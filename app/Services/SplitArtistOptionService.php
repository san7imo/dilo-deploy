<?php

namespace App\Services;

use App\Models\Artist;
use Illuminate\Support\Collection;

class SplitArtistOptionService
{
    /**
     * @return Collection<int, array{id:int, artist_id:int, artist_name:string, name:string}>
     */
    public function internalArtists(): Collection
    {
        return Artist::query()
            ->internal()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Artist $artist): array => [
                'id' => (int) $artist->id,
                'artist_id' => (int) $artist->id,
                'artist_name' => $artist->name,
                'name' => $artist->name,
            ])
            ->values();
    }

    /**
     * @return Collection<int, array{id:int, artist_id:int, artist_name:string, name:string, user_id:int|null, email:string|null, stage_name:string|null, account_status:string}>
     */
    public function externalArtists(): Collection
    {
        return Artist::query()
            ->external()
            ->select(['id', 'name', 'user_id'])
            ->with('user:id,name,stage_name,email')
            ->orderBy('name')
            ->get()
            ->map(function (Artist $artist): array {
                $user = $artist->user;

                return [
                    'id' => (int) $artist->id,
                    'artist_id' => (int) $artist->id,
                    'artist_name' => $artist->name,
                    'name' => $artist->name,
                    'user_id' => $user?->id ? (int) $user->id : null,
                    'email' => $user?->email,
                    'stage_name' => $user?->stage_name,
                    'account_status' => $user ? 'active' : 'pending',
                ];
            })
            ->values();
    }

    /**
     * @return array{internalArtists: Collection<int, array<string, mixed>>, externalArtists: Collection<int, array<string, mixed>>}
     */
    public function grouped(): array
    {
        return [
            'internalArtists' => $this->internalArtists(),
            'externalArtists' => $this->externalArtists(),
        ];
    }
}
