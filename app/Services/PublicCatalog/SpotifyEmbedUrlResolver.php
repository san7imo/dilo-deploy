<?php

namespace App\Services\PublicCatalog;

class SpotifyEmbedUrlResolver
{
    private const SUPPORTED_ENTITY_TYPES = [
        'album',
        'artist',
        'episode',
        'playlist',
        'show',
        'track',
    ];

    public function resolve(?string $spotifyUrl): ?string
    {
        $spotifyUrl = trim((string) $spotifyUrl);

        if ($spotifyUrl === '') {
            return null;
        }

        $entity = str_starts_with($spotifyUrl, 'spotify:')
            ? $this->entityFromUri($spotifyUrl)
            : $this->entityFromUrl($spotifyUrl);

        if ($entity === null) {
            return null;
        }

        return sprintf(
            'https://open.spotify.com/embed/%s/%s',
            $entity['type'],
            $entity['id'],
        );
    }

    private function entityFromUri(string $spotifyUri): ?array
    {
        $parts = explode(':', $spotifyUri);

        if (count($parts) < 3 || $parts[0] !== 'spotify') {
            return null;
        }

        return $this->validatedEntity($parts[1], $parts[2]);
    }

    private function entityFromUrl(string $spotifyUrl): ?array
    {
        $parts = parse_url($spotifyUrl);
        $host = strtolower($parts['host'] ?? '');

        if (! in_array($host, ['open.spotify.com', 'www.open.spotify.com'], true)) {
            return null;
        }

        $pathSegments = collect(explode('/', trim($parts['path'] ?? '', '/')))
            ->filter()
            ->values()
            ->all();

        if ($pathSegments === []) {
            return null;
        }

        if (strtolower($pathSegments[0]) === 'embed') {
            array_shift($pathSegments);
        }

        if (isset($pathSegments[0]) && preg_match('/^([a-z]{2}(-[a-z]{2})?|intl-[a-z]{2})$/i', $pathSegments[0])) {
            array_shift($pathSegments);
        }

        if (count($pathSegments) < 2) {
            return null;
        }

        return $this->validatedEntity($pathSegments[0], $pathSegments[1]);
    }

    private function validatedEntity(string $type, string $id): ?array
    {
        $type = strtolower($type);
        $id = preg_replace('/[^A-Za-z0-9]/', '', $id);

        if (! in_array($type, self::SUPPORTED_ENTITY_TYPES, true) || $id === '') {
            return null;
        }

        return [
            'type' => $type,
            'id' => $id,
        ];
    }
}
