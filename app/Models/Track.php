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
        'isrc',
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

    public function splitAgreements()
    {
        return $this->hasMany(TrackSplitAgreement::class);
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

    public function setIsrcAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['isrc'] = null;
            return;
        }

        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $value));
        $this->attributes['isrc'] = $normalized !== '' ? $normalized : null;
    }

    /**
     * Determina si la pista pertenece a un "single".
     */
    public function isPartOfSingle(): bool
    {
        return $this->release && $this->release->isSingle();
    }
}
