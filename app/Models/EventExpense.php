<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventExpense extends Model
{
    protected $fillable = [
        'event_id',
        'expense_date',
        'description',
        'amount_original',
        'currency',
        'exchange_rate_to_base',
        'amount_base',
    ];

    protected $casts = [
        'expense_date' => 'date',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
