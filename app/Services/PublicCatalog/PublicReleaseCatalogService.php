<?php

namespace App\Services\PublicCatalog;

use App\Models\Release;
use Illuminate\Pagination\LengthAwarePaginator;

class PublicReleaseCatalogService
{
    public function __construct(
        private readonly PublicReleasePresenter $releasePresenter,
    ) {}

    public function paginatedReleases(int $perPage = 12): LengthAwarePaginator
    {
        return Release::query()
            ->with(['artist', 'tracks'])
            ->whereHas('artist', fn ($artistQuery) => $artistQuery->publicProfileVisible())
            ->orderByDesc('release_date')
            ->orderBy('title')
            ->paginate($perPage)
            ->through(fn (Release $release) => $this->releasePresenter->present($release, false));
    }

    public function releaseBySlug(string $slug): array
    {
        $release = Release::query()
            ->with(['artist', 'tracks.artists', 'tracks.release.artist'])
            ->whereHas('artist', fn ($artistQuery) => $artistQuery->publicProfileVisible())
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug);

                if (is_numeric($slug)) {
                    $query->orWhere('id', (int) $slug);
                }
            })
            ->firstOrFail();

        return $this->releasePresenter->present($release);
    }
}
