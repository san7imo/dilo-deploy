<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoyaltyAllocationRecalculation extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'royalty_statement_id',
        'triggered_by_user_id',
        'trigger_source',
        'reason',
        'lines_total',
        'lines_matched',
        'master_allocations_count',
        'composition_allocations_count',
        'warnings',
        'context',
    ];

    protected $casts = [
        'warnings' => 'array',
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function statement()
    {
        return $this->belongsTo(RoyaltyStatement::class, 'royalty_statement_id');
    }

    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }
}

