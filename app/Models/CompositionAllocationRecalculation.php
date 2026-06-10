<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompositionAllocationRecalculation extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'composition_royalty_statement_id',
        'triggered_by_user_id',
        'trigger_source',
        'reason',
        'lines_total',
        'lines_matched',
        'allocations_count',
        'allocations_total_usd',
        'warnings',
        'context',
    ];

    protected $casts = [
        'allocations_total_usd' => 'decimal:6',
        'warnings' => 'array',
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function statement(): BelongsTo
    {
        return $this->belongsTo(CompositionRoyaltyStatement::class, 'composition_royalty_statement_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }
}
