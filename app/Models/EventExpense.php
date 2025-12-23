<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventExpense extends Model
{
    protected $fillable = [
        'event_id',
        'expense_date',
        'description',
        'name',
        'category',
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
}
