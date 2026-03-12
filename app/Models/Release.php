<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use App\Traits\SoftDeletesUniqueValues;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\HasImages;

class Release extends Model
{
    use HasFactory, HasImages, LogsAuditTrail, SoftDeletes, SoftDeletesUniqueValues;

    protected array $softDeleteUniqueColumns = ['slug'];

    /**
     * Campos de imagen gestionados automáticamente por ImageKit.
     */
    protected array $imageFields = ['cover'];

    /**
     * Campos asignables en masa.
     */
    protected $fillable = [
        'artist_id',
        'title',
        'upc',
        'slug',
        'cover_url',  // Portada del lanzamiento
        'cover_id',   // ID en ImageKit (para eliminar o reemplazar)
        'release_date',
        'type',
        'spotify_url',
        'youtube_url',
        'apple_music_url',
        'deezer_url',
        'amazon_music_url',
        'soundcloud_url',
        'tidal_url',
        'description',
        'extra',
    ];

    /**
     * Casts automáticos.
     */
    protected $casts = [
        'release_date' => 'date',
        'extra' => 'array',
        'deleted_unique_snapshot' => 'array',
    ];

    /**
     * Atributos que siempre se incluyen en la serialización.
     */
    protected $appends = ['optimized_cover_url', 'platforms'];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */
    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function tracks()
    {
        return $this->hasMany(Track::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Mutadores
    |--------------------------------------------------------------------------
    */
    protected static function booted()
    {
        static::creating(function ($release) {
            if (empty($release->slug)) {
                $release->slug = Str::slug($release->title);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers y Accesores
    |--------------------------------------------------------------------------
    */

    /**
     * Determina si el lanzamiento es un single.
     */
    public function isSingle(): bool
    {
        return strtolower($this->type) === 'single';
    }

    /**
     * Determina si el lanzamiento es un álbum.
     */
    public function isAlbum(): bool
    {
        return strtolower($this->type) === 'album';
    }

    /**
     * Accesor: Devuelve plataformas disponibles como array.
     */
    public function getPlatformsAttribute(): array
    {
        $platforms = [
            'spotify' => $this->spotify_url,
            'youtube' => $this->youtube_url,
            'apple_music' => $this->apple_music_url,
            'deezer' => $this->deezer_url,
            'amazon_music' => $this->amazon_music_url,
            'soundcloud' => $this->soundcloud_url,
            'tidal' => $this->tidal_url,
        ];

        return collect($platforms)
            ->filter()
            ->map(fn($url, $platform) => [
                'name' => ucfirst(str_replace('_', ' ', $platform)),
                'url' => $url,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Accesor: URL optimizada de la portada para frontend.
     */
    public function getOptimizedCoverUrlAttribute(): ?string
    {
        return $this->cover_url
            ? "{$this->cover_url}?tr=w-800,h-800,q-85,fo-auto"
            : null;
    }

    public function setUpcAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['upc'] = null;
            return;
        }

        $normalized = preg_replace('/\D/', '', (string) $value);
        $this->attributes['upc'] = $normalized !== '' ? $normalized : null;
    }
}
