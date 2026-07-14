<?php

namespace App\Services\PublicCatalog;

use App\Models\Track;

class PublicTrackPresenter
{
    public function __construct(
        private readonly StreamingPlatformLinkBuilder $platformLinkBuilder,
        private readonly SpotifyEmbedUrlResolver $spotifyEmbedUrlResolver,
    ) {}

    public function present(Track $track): array
    {
        $track->loadMissing(['release.artist', 'artists']);

        return [
            'id' => $track->id,
            'title' => $track->title,
            'track_number' => $track->track_number,
            'duration' => $track->duration,
            'isrc' => $track->isrc,
            'audio_url' => $track->audio_url,
            'cover_url' => $track->cover_url,
            'effective_cover_url' => $track->effective_cover_url,
            'optimized_cover_url' => $track->optimized_cover_url,
            'spotify_url' => $track->spotify_url,
            'spotify_embed_url' => $this->spotifyEmbedUrlResolver->resolve($track->spotify_url),
            'platforms' => $this->platformLinkBuilder->fromModel($track),
            'release' => $track->release ? [
                'id' => $track->release->id,
                'title' => $track->release->title,
                'slug' => $track->release->slug,
                'type' => $track->release->type,
                'release_date' => optional($track->release->release_date)->toDateString(),
                'cover_url' => $track->release->cover_url,
                'optimized_cover_url' => $track->release->optimized_cover_url,
                'artist' => $track->release->artist ? [
                    'id' => $track->release->artist->id,
                    'name' => $track->release->artist->name,
                    'slug' => $track->release->artist->slug,
                ] : null,
            ] : null,
            'artists' => $track->artists
                ->map(fn ($artist) => [
                    'id' => $artist->id,
                    'name' => $artist->name,
                    'slug' => $artist->slug,
                ])
                ->values()
                ->all(),
        ];
    }
}
