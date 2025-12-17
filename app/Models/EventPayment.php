<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventPayment extends Model
{
    protected $fillable = [
        'event_id',
        'payment_date',
        'amount_original',
        'currency',
        'exchange_rate_to_base',
        'amount_base',
        'payment_method',
        'is_advance',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'is_advance' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */
    
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
