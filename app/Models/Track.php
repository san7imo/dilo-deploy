<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasImages;

class Track extends Model
{
    use HasFactory, HasImages;

    /**
     * Campos de imagen gestionados automáticamente por ImageKit.
     */
    protected array $imageFields = ['cover'];

    /**
     * Campos asignables en masa.
     */
    protected $fillable = [
        'release_id',
        'title',
        'track_number',
        'duration',
        'cover_url',  // opcional, si la pista tiene su propio arte
        'cover_id',   // ID del archivo en ImageKit (para eliminar/reemplazar)
        'spotify_url',
        'youtube_url',
        'apple_music_url',
        'deezer_url',
        'amazon_music_url',
        'soundcloud_url',
        'tidal_url',
        'preview_url',
    ];

    /**
     * Atributos agregados al serializar.
     * Esto permite que el frontend siga usando "audio_url" sin cambiar componentes.
     */
    protected $appends = ['audio_url', 'effective_cover_url', 'optimized_cover_url'];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */
    public function release()
    {
        return $this->belongsTo(Release::class);
    }

    public function artists()
    {
        return $this->belongsToMany(Artist::class, 'track_artist');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors / Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Alias virtual: audio_url -> preview_url
     */
    public function getAudioUrlAttribute(): ?string
    {
        return $this->preview_url ?: null;
    }

    /**
     * Portada efectiva de la pista.
     */
    public function getEffectiveCoverUrlAttribute(): ?string
    {
        return $this->cover_url ?: ($this->release?->cover_url ?? null);
    }

    /**
     * Portada optimizada (para frontend).
     */
    public function getOptimizedCoverUrlAttribute(): ?string
    {
        $url = $this->effective_cover_url;
        return $url ? "{$url}?tr=w-800,h-800,q-85,fo-auto" : null;
    }

    /**
     * Determina si la pista pertenece a un "single".
     */
    public function isPartOfSingle(): bool
    {
        return $this->release && $this->release->isSingle();
    }
}
