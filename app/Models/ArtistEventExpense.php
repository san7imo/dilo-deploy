<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArtistEventExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'artist_id',
        'name',
        'description',
        'category',
        'expense_date',
        'amount_original',
        'currency',
        'exchange_rate_to_base',
        'amount_base',
        'receipt_url',
        'receipt_id',
        'notes',
        'created_by_user_id',
        'approved_at',
    ];

    // casts automaticos 

    protected $casts = [
        'expense_date' => 'date',
        'amount_original' => 'decimal:2',
        'exchange_rate_to_base' => 'decimal:4',
        'amount_base' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Atributos computados
    #[Attribute]
    public function isApproved(): Attribute
    {
        return Attribute::make(
            get: fn() => !is_null($this->approved_at),
        );
    }

    // relaciones
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class, 'artist_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('approved_at');
    }

    public function scopeByEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeByArtist($query, $artistId)
    {
        return $query->where('artist_id', $artistId);
    }

    // Metodos auxiliares
    public function approve(): void
    {
        $this->approved_at = now();
        $this->save();
    }
}
