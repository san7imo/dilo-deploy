<?php

namespace App\Services\PublicCatalog;

use Illuminate\Database\Eloquent\Model;

class StreamingPlatformLinkBuilder
{
    private const PLATFORM_COLUMNS = [
        'spotify' => [
            'name' => 'Spotify',
            'column' => 'spotify_url',
            'icon' => 'simple-icons:spotify',
        ],
        'youtube' => [
            'name' => 'YouTube',
            'column' => 'youtube_url',
            'icon' => 'simple-icons:youtube',
        ],
        'apple_music' => [
            'name' => 'Apple Music',
            'column' => 'apple_music_url',
            'icon' => 'simple-icons:applemusic',
        ],
        'deezer' => [
            'name' => 'Deezer',
            'column' => 'deezer_url',
            'icon' => 'simple-icons:deezer',
        ],
        'amazon_music' => [
            'name' => 'Amazon Music',
            'column' => 'amazon_music_url',
            'icon' => 'simple-icons:amazonmusic',
        ],
        'soundcloud' => [
            'name' => 'SoundCloud',
            'column' => 'soundcloud_url',
            'icon' => 'simple-icons:soundcloud',
        ],
        'tidal' => [
            'name' => 'Tidal',
            'column' => 'tidal_url',
            'icon' => 'simple-icons:tidal',
        ],
    ];

    public function fromModel(Model $model): array
    {
        return collect(self::PLATFORM_COLUMNS)
            ->map(function (array $platform, string $key) use ($model) {
                $url = $model->{$platform['column']} ?? null;

                if (! is_string($url) || trim($url) === '') {
                    return null;
                }

                return [
                    'key' => $key,
                    'name' => $platform['name'],
                    'url' => trim($url),
                    'icon' => $platform['icon'],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
