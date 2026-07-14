<?php

namespace App\Services\PublicCatalog;

use App\Models\Release;

class PublicReleasePresenter
{
    public function __construct(
        private readonly StreamingPlatformLinkBuilder $platformLinkBuilder,
        private readonly SpotifyEmbedUrlResolver $spotifyEmbedUrlResolver,
        private readonly PublicTrackPresenter $trackPresenter,
    ) {}

    public function present(Release $release, bool $includeTracks = true): array
    {
        $release->loadMissing(['artist']);

        if ($includeTracks) {
            $release->loadMissing(['tracks.artists', 'tracks.release.artist']);
        }

        return [
            'id' => $release->id,
            'title' => $release->title,
            'slug' => $release->slug,
            'upc' => $release->upc,
            'type' => $release->type,
            'description' => $release->description,
            'cover_url' => $release->cover_url,
            'optimized_cover_url' => $release->optimized_cover_url,
            'release_date' => optional($release->release_date)->toDateString(),
            'spotify_url' => $release->spotify_url,
            'spotify_embed_url' => $this->spotifyEmbedUrlResolver->resolve($release->spotify_url),
            'platforms' => $this->platformLinkBuilder->fromModel($release),
            'artist' => $release->artist ? [
                'id' => $release->artist->id,
                'name' => $release->artist->name,
                'slug' => $release->artist->slug,
            ] : null,
            'tracks' => $includeTracks
                ? $release->tracks
                    ->sortBy(fn ($track) => $track->track_number ?? $track->id)
                    ->map(fn ($track) => $this->trackPresenter->present($track))
                    ->values()
                    ->all()
                : [],
        ];
    }
}
