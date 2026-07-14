<?php

namespace Tests\Unit\PublicCatalog;

use App\Services\PublicCatalog\SpotifyEmbedUrlResolver;
use PHPUnit\Framework\TestCase;

class SpotifyEmbedUrlResolverTest extends TestCase
{
    public function test_it_builds_embed_url_from_open_spotify_track_url(): void
    {
        $resolver = new SpotifyEmbedUrlResolver;

        $this->assertSame(
            'https://open.spotify.com/embed/track/7ouMYWpwJ422jRcDASZB7P',
            $resolver->resolve('https://open.spotify.com/track/7ouMYWpwJ422jRcDASZB7P?si=abc123'),
        );
    }

    public function test_it_builds_embed_url_from_localized_open_spotify_track_url(): void
    {
        $resolver = new SpotifyEmbedUrlResolver;

        $this->assertSame(
            'https://open.spotify.com/embed/track/7ouMYWpwJ422jRcDASZB7P',
            $resolver->resolve('https://open.spotify.com/intl-es/track/7ouMYWpwJ422jRcDASZB7P'),
        );
    }

    public function test_it_builds_embed_url_from_spotify_uri(): void
    {
        $resolver = new SpotifyEmbedUrlResolver;

        $this->assertSame(
            'https://open.spotify.com/embed/track/7ouMYWpwJ422jRcDASZB7P',
            $resolver->resolve('spotify:track:7ouMYWpwJ422jRcDASZB7P'),
        );
    }

    public function test_it_returns_null_for_links_that_cannot_be_resolved_without_external_lookup(): void
    {
        $resolver = new SpotifyEmbedUrlResolver;

        $this->assertNull($resolver->resolve('https://spotify.link/example'));
        $this->assertNull($resolver->resolve('https://example.com/track/7ouMYWpwJ422jRcDASZB7P'));
        $this->assertNull($resolver->resolve(null));
    }
}
