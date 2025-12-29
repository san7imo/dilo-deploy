<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Traits\HasImages;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

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
        'event_type',
        'country',
        'city',
        'venue_address',
        'show_fee_total',
        'currency',
        'advance_percentage',
        'advance_expected',
        'full_payment_due_date',
        'status',
    ];

    /**
     * Casts automáticos.
     */
    protected $casts = [
        'event_date' => 'date',
        'is_paid' => 'boolean',
        'advance_expected' => 'boolean',
        'full_payment_due_date' => 'date',
        'show_fee_total' => 'decimal:2',
        'advance_percentage' => 'decimal:2',
    ];

    /**
     * Atributos que siempre se incluyen en la serialización.
     */
    protected $appends = [
        'is_upcoming',
        'is_past',
        'poster_optimized_url',
        'net_base',
        'artist_share_estimated_base',
        'label_share_estimated_base'
    ];

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

    public function roadManagers()
    {
        return $this->belongsToMany(User::class, 'event_road_manager')
            ->withPivot('payment_confirmed_at')
            ->withTimestamps();
    }

    public function expenses()
    {
        return $this->hasMany(EventExpense::class);
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

    /**
     * Resultado neto del evento (ingresos - gastos).
     */
    public function getNetBaseAttribute(): float
    {
        $totalPaid = $this->total_paid_base ?? 0;
        $totalExpenses = $this->total_expenses_base ?? 0;
        return round($totalPaid - $totalExpenses, 2);
    }

    /**
     * 70% del resultado neto para el artista.
     */
    public function getArtistShareEstimatedBaseAttribute(): float
    {
        return round($this->net_base * 0.70, 2);
    }

    /**
     * 30% del resultado neto para la compañía.
     */
    public function getLabelShareEstimatedBaseAttribute(): float
    {
        return round($this->net_base * 0.30, 2);
    }

    public function scopeVisibleForUser(Builder $query, $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('roadmanager')) {
            return $query->whereHas('roadManagers', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        if ($user->hasRole('artist') && $user->artist) {
            return $query->where('main_artist_id', $user->artist->id);
        }
        return $query->whereRaw('1 = 0');
    }
}
