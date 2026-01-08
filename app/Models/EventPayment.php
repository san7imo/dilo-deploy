<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class EventPayment extends Model
{
    protected $fillable = [
        'event_id',
        'created_by',
        'payment_date',
        'amount_original',
        'currency',
        'exchange_rate_to_base',
        'amount_base',
        'payment_method',
        'is_advance',
        'notes',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
