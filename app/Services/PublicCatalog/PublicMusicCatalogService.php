<?php

namespace App\Services\PublicCatalog;

use App\Models\Artist;
use App\Models\Release;
use App\Models\Track;
use Illuminate\Pagination\LengthAwarePaginator;

class PublicMusicCatalogService
{
    public function __construct(
        private readonly PublicTrackPresenter $trackPresenter,
    ) {}

    public function paginatedTracks(int $perPage = 12, array $filters = []): LengthAwarePaginator
    {
        return Track::query()
            ->with(['release.artist', 'artists'])
            ->when($filters['artist'] ?? null, function ($query, string $artistSlug) {
                $query->where(function ($query) use ($artistSlug) {
                    $query
                        ->whereHas('artists', fn ($artistQuery) => $artistQuery->where('slug', $artistSlug))
                        ->orWhereHas('release.artist', fn ($artistQuery) => $artistQuery->where('slug', $artistSlug));
                });
            })
            ->when($filters['release'] ?? null, function ($query, string $releaseSlug) {
                $query->whereHas('release', fn ($releaseQuery) => $releaseQuery->where('slug', $releaseSlug));
            })
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('tracks.title', 'like', "%{$search}%")
                        ->orWhereHas('release', fn ($releaseQuery) => $releaseQuery->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('artists', fn ($artistQuery) => $artistQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('release_id')
            ->orderBy('track_number')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Track $track) => $this->trackPresenter->present($track));
    }

    public function trackById(int $id): array
    {
        $track = Track::query()
            ->with(['release.artist', 'artists'])
            ->findOrFail($id);

        return $this->trackPresenter->present($track);
    }

    public function filterOptions(): array
    {
        return [
            'artists' => Artist::query()
                ->publicProfileVisible()
                ->where(function ($query) {
                    $query
                        ->whereHas('tracks')
                        ->orWhereHas('releases.tracks');
                })
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Artist $artist) => [
                    'name' => $artist->name,
                    'slug' => $artist->slug,
                ])
                ->values()
                ->all(),
            'releases' => Release::query()
                ->whereHas('tracks')
                ->with('artist:id,name,slug')
                ->orderByDesc('release_date')
                ->orderBy('title')
                ->get(['id', 'artist_id', 'title', 'slug', 'release_date'])
                ->map(fn (Release $release) => [
                    'title' => $release->title,
                    'slug' => $release->slug,
                    'artist_name' => $release->artist?->name,
                ])
                ->values()
                ->all(),
        ];
    }
}
