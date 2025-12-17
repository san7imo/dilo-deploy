<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Traits\HasImages;
use Illuminate\Database\Eloquent\Builder;

class Event extends Model
{
    use HasFactory, HasImages;

    /**
     * Campos de imagen gestionados automáticamente por ImageKit.
     */
    protected array $imageFields = ['poster'];

    /**
     * Campos asignables en masa.
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'location',
        'event_date',
        'is_paid',
        'main_artist_id',
        'poster_url', // Afiche individual del evento
        'poster_id',  // ID del archivo en ImageKit
    ];

    /**
     * Casts automáticos.
     */
    protected $casts = [
        'event_date' => 'date',
        'is_paid' => 'boolean',
    ];

    /**
     * Atributos que siempre se incluyen en la serialización.
     */
    protected $appends = ['is_upcoming', 'is_past', 'poster_optimized_url'];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */
    public function artists()
    {
        return $this->belongsToMany(Artist::class, 'artist_event');
    }

    public function mainArtist()
    {
        return $this->belongsTo(Artist::class, 'main_artist_id');
    }

    public function mainEvents()
    {
        return $this->hasMany(Event::class, 'main_artist_id');
    }

    public function payments()
    {
        return $this->hasMany(EventPayment::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Mutadores
    |--------------------------------------------------------------------------
    */
    protected static function booted()
    {
        static::creating(function ($event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->title);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accesores auxiliares
    |--------------------------------------------------------------------------
    */

    /**
     * Retorna si el evento aún no ha ocurrido.
     */
    public function getIsUpcomingAttribute(): bool
    {
        return $this->event_date && $this->event_date->isFuture();
    }

    /**
     * Retorna si el evento ya finalizó.
     */
    public function getIsPastAttribute(): bool
    {
        return $this->event_date && $this->event_date->isPast();
    }

    /**
     * Devuelve la versión optimizada del afiche (para uso en frontend).
     */
    public function getPosterOptimizedUrlAttribute(): ?string
    {
        return $this->poster_url
            ? "{$this->poster_url}?tr=w-1200,h-800,q-85,fo-auto"
            : null;
    }

    public function scopeVisibleForUser(Builder $query, $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('artist') && $user->artist) {
            return $query->where('main_artist_id', $user->artist->id);
        }
        return $query->whereRaw('1 = 0');
    }
}
