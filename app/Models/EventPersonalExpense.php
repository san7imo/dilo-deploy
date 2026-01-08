<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventPersonalExpense extends Model
{
    protected $fillable = [
        'event_id',
        'artist_id',
        'expense_date',
        'expense_type',
        'name',
        'description',
        'payment_method',
        'recipient',
        'amount_original',
        'currency',
        'exchange_rate_to_base',
        'amount_base',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount_original' => 'decimal:2',
        'exchange_rate_to_base' => 'decimal:6',
        'amount_base' => 'decimal:2',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }
}
