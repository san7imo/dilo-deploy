<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Traits\HasImages;

class Artist extends Model
{
    use HasFactory, HasImages;

    /**
     * Campos de imagen gestionados automáticamente por ImageKit.
     */
    protected array $imageFields = [
        'banner_home',
        'banner_artist',
        'carousel_home',
        'carousel_discography',
    ];

    /**
     * Campos asignables en masa.
     */
    protected $fillable = [
        'name',
        'slug',
        'bio',
        'country',
        'genre_id',
        'youtube_url',

        // Imágenes
        'banner_home_url', 'banner_home_id',
        'banner_artist_url', 'banner_artist_id',
        'carousel_home_url', 'carousel_home_id',
        'carousel_discography_url', 'carousel_discography_id',

        // Redes sociales (JSON)
        'social_links',

        // opcionales
        // 'is_featured',
    ];

    /**
     * Casts automáticos.
     */
    protected $casts = [
        'social_links' => 'array',
    ];

    /**
     * Atributos que siempre se incluyen en la serialización.
     */
    protected $appends = ['image_url', 'playlist_image_url', 'main_image_url', 'social_links_formatted'];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function genre()
    {
        return $this->belongsTo(Genre::class, 'genre_id', 'id');
    }

    public function releases()
    {
        return $this->hasMany(Release::class, 'artist_id', 'id');
    }

    public function tracks()
    {
        return $this->belongsToMany(Track::class, 'track_artist', 'artist_id', 'track_id')
            ->withTimestamps();
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'artist_event', 'artist_id', 'event_id')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Mutadores y eventos del modelo
    |--------------------------------------------------------------------------
    */
    protected static function booted(): void
    {
        static::creating(function ($artist) {
            if (empty($artist->slug)) {
                $artist->slug = Str::slug($artist->name);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos auxiliares
    |--------------------------------------------------------------------------
    */

    /** Convertir social_links del formato [{ platform, url }] al formato { platform: url } */
    public function getSocialLinksFormattedAttribute(): array
    {
        $result = [];
        $socialLinks = $this->social_links ?? [];

        // Si es array de objetos [{ platform, url }]
        if (is_array($socialLinks) && !empty($socialLinks) && isset($socialLinks[0]['platform'])) {
            foreach ($socialLinks as $item) {
                if (is_array($item) && isset($item['platform']) && isset($item['url'])) {
                    $key = strtolower($item['platform']);
                    $result[$key] = $item['url'];
                }
            }
        } // Si ya es un objeto { platform: url }
        elseif (is_array($socialLinks) || is_object($socialLinks)) {
            foreach ((array)$socialLinks as $key => $url) {
                if (is_string($url) && !empty($url)) {
                    $result[strtolower($key)] = $url;
                }
            }
        }

        // Asegurar que todas las claves existan
        $platforms = ['spotify', 'youtube', 'instagram', 'tiktok', 'facebook', 'x', 'apple', 'amazon'];
        foreach ($platforms as $platform) {
            if (!isset($result[$platform])) {
                $result[$platform] = "";
            }
        }

        return $result;
    }

    /** Redes en formato [{ platform, url }] */
    public function getSocialLinksListAttribute(): array
    {
        return collect($this->social_links ?? [])
            ->map(fn($url, $platform) => [
                'platform' => ucfirst($platform),
                'url' => $url,
            ])
            ->values()
            ->toArray();
    }

    public function hasSocial(string $platform): bool
    {
        return isset($this->social_links[$platform]);
    }

    public function getSocial(string $platform): ?string
    {
        return $this->social_links[$platform] ?? null;
    }

    /** Imagen principal de fallback */
    public function getMainImageUrlAttribute(): ?string
    {
        return $this->banner_artist_url
            ?? $this->banner_home_url
            ?? $this->carousel_home_url
            ?? $this->carousel_discography_url;
    }

    /** Imagen para carrusel y playlists (usa carousel_home_url) */
    public function getImageUrlAttribute(): ?string
    {
        return $this->carousel_home_url;
    }

    /** Imagen para tarjeta de playlists (usa carousel_discography_url) */
    public function getPlaylistImageUrlAttribute(): ?string
    {
        return $this->carousel_discography_url;
    }
}
