<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Release;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMusicCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_tracks_include_release_artists_platforms_and_spotify_embed_url(): void
    {
        $artist = Artist::query()->create([
            'name' => 'Artista Publico',
            'artist_origin' => 'internal',
            'has_public_profile' => true,
        ]);

        $release = Release::query()->create([
            'artist_id' => $artist->id,
            'title' => 'Release Publico',
            'cover_url' => 'https://cdn.example.com/release.jpg',
            'release_date' => '2026-05-01',
            'type' => 'single',
        ]);

        $track = Track::query()->create([
            'release_id' => $release->id,
            'title' => 'Cancion Publica',
            'track_number' => 1,
            'duration' => '03:15',
            'spotify_url' => 'https://open.spotify.com/track/7ouMYWpwJ422jRcDASZB7P?si=abc123',
            'youtube_url' => 'https://youtube.com/watch?v=demo',
        ]);

        $track->artists()->sync([$artist->id]);

        $this->get(route('public.songs.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Songs/Index')
                ->has('tracks.data', 1)
                ->has('filterOptions.artists', 1)
                ->has('filterOptions.releases', 1)
                ->where('filterOptions.artists.0.slug', $artist->slug)
                ->where('filterOptions.releases.0.slug', $release->slug)
                ->where('tracks.data.0.title', 'Cancion Publica')
                ->where('tracks.data.0.release.title', 'Release Publico')
                ->where('tracks.data.0.artists.0.name', 'Artista Publico')
                ->where('tracks.data.0.platforms.0.key', 'spotify')
                ->where('tracks.data.0.platforms.1.key', 'youtube')
                ->where(
                    'tracks.data.0.spotify_embed_url',
                    'https://open.spotify.com/embed/track/7ouMYWpwJ422jRcDASZB7P',
                )
            );
    }

    public function test_public_artists_index_limits_song_summary_to_five_tracks_per_artist(): void
    {
        $artist = Artist::query()->create([
            'name' => 'Artista Con Catalogo',
            'artist_origin' => 'internal',
            'has_public_profile' => true,
        ]);

        $release = Release::query()->create([
            'artist_id' => $artist->id,
            'title' => 'Release Largo',
            'release_date' => '2026-05-01',
            'type' => 'album',
        ]);

        foreach (range(1, 6) as $trackNumber) {
            $track = Track::query()->create([
                'release_id' => $release->id,
                'title' => "Cancion {$trackNumber}",
                'track_number' => $trackNumber,
            ]);

            $track->artists()->sync([$artist->id]);
        }

        $this->get(route('public.artists.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Artists/Index')
                ->has('artists.data', 1)
                ->has('artists.data.0.tracks', 5)
            );
    }

    public function test_public_releases_pages_render_with_tracks_and_embeds(): void
    {
        $artist = Artist::query()->create([
            'name' => 'Artista Release',
            'artist_origin' => 'internal',
            'has_public_profile' => true,
        ]);

        $release = Release::query()->create([
            'artist_id' => $artist->id,
            'title' => 'Release Navegable',
            'cover_url' => 'https://cdn.example.com/release.jpg',
            'release_date' => '2026-05-01',
            'type' => 'single',
            'spotify_url' => 'https://open.spotify.com/album/2up3OPMp9Tb4dAKM2erWXQ',
        ]);

        $track = Track::query()->create([
            'release_id' => $release->id,
            'title' => 'Cancion Del Release',
            'track_number' => 1,
            'spotify_url' => 'https://open.spotify.com/track/7ouMYWpwJ422jRcDASZB7P',
        ]);

        $track->artists()->sync([$artist->id]);

        $this->get(route('public.releases.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Releases/Index')
                ->has('releases.data', 1)
                ->where('releases.data.0.title', 'Release Navegable')
                ->where('releases.data.0.artist.name', 'Artista Release')
            );

        $this->get(route('public.releases.show', $release->slug))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Releases/Show')
                ->where('release.title', 'Release Navegable')
                ->where('release.tracks.0.title', 'Cancion Del Release')
                ->where('release.tracks.0.spotify_embed_url', 'https://open.spotify.com/embed/track/7ouMYWpwJ422jRcDASZB7P')
            );
    }
}
