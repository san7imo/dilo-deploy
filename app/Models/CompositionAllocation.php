<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompositionAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'composition_royalty_statement_id',
        'composition_royalty_line_id',
        'composition_id',
        'composition_split_set_id',
        'composition_split_participant_id',
        'share_pool',
        'line_type',
        'party_user_id',
        'party_artist_id',
        'party_email',
        'role',
        'activity_month_date',
        'split_percentage',
        'gross_amount_usd',
        'allocated_amount_usd',
        'currency',
        'status',
    ];

    protected $casts = [
        'activity_month_date' => 'date',
        'split_percentage' => 'decimal:4',
        'gross_amount_usd' => 'decimal:6',
        'allocated_amount_usd' => 'decimal:6',
    ];

    public function statement(): BelongsTo
    {
        return $this->belongsTo(CompositionRoyaltyStatement::class, 'composition_royalty_statement_id');
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(CompositionRoyaltyLine::class, 'composition_royalty_line_id');
    }

    public function composition(): BelongsTo
    {
        return $this->belongsTo(Composition::class);
    }

    public function splitSet(): BelongsTo
    {
        return $this->belongsTo(CompositionSplitSet::class, 'composition_split_set_id');
    }

    public function splitParticipant(): BelongsTo
    {
        return $this->belongsTo(CompositionSplitParticipant::class, 'composition_split_participant_id');
    }
}

