<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoyaltyStatementLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'royalty_statement_id',
        'track_id',
        'isrc',
        'upc',
        'track_title',
        'channel',
        'country',
        'activity_period_text',
        'activity_month_date',
        'units',
        'net_total_usd',
        'line_hash',
        'raw',
    ];

    protected $casts = [
        'activity_month_date' => 'date',
        'units' => 'integer',
        'net_total_usd' => 'decimal:6',
        'raw' => 'array',
    ];

    public function statement()
    {
        return $this->belongsTo(RoyaltyStatement::class, 'royalty_statement_id');
    }

    public function track()
    {
        return $this->belongsTo(Track::class);
    }
}
