<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompositionRoyaltyLine extends Model
{
    use HasFactory, LogsAuditTrail, SoftDeletes;

    public const MATCH_STATUS_MATCHED = 'matched';
    public const MATCH_STATUS_UNMATCHED = 'unmatched';
    public const MATCH_STATUS_AMBIGUOUS = 'ambiguous';

    public const LINE_TYPE_PERFORMANCE = 'performance';
    public const LINE_TYPE_MECHANICAL = 'mechanical';

    protected $fillable = [
        'composition_royalty_statement_id',
        'composition_id',
        'line_type',
        'source_line_id',
        'external_reference',
        'composition_iswc',
        'composition_title',
        'source_name',
        'territory_code',
        'activity_period_text',
        'activity_month_date',
        'units',
        'amount_usd',
        'currency',
        'line_hash',
        'match_status',
        'match_meta',
        'raw',
    ];

    protected $casts = [
        'activity_month_date' => 'date',
        'amount_usd' => 'decimal:6',
        'match_meta' => 'array',
        'raw' => 'array',
    ];

    public function statement(): BelongsTo
    {
        return $this->belongsTo(CompositionRoyaltyStatement::class, 'composition_royalty_statement_id');
    }

    public function composition(): BelongsTo
    {
        return $this->belongsTo(Composition::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CompositionAllocation::class, 'composition_royalty_line_id');
    }
}

