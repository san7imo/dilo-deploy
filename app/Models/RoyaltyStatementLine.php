<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoyaltyStatementLine extends Model
{
    use HasFactory, LogsAuditTrail, SoftDeletes;

    public const MATCH_STATUS_MATCHED = 'matched';
    public const MATCH_STATUS_UNMATCHED = 'unmatched';
    public const MATCH_STATUS_AMBIGUOUS = 'ambiguous';
    public const MATCH_STATUS_DUPLICATE = 'duplicate';
    public const MATCH_STATUS_REFERENCE_ONLY = 'reference_only';

    protected $fillable = [
        'royalty_statement_id',
        'track_id',
        'match_status',
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
        'match_meta',
    ];

    protected $casts = [
        'activity_month_date' => 'date',
        'units' => 'integer',
        'net_total_usd' => 'decimal:6',
        'raw' => 'array',
        'match_meta' => 'array',
    ];

    public function statement()
    {
        return $this->belongsTo(RoyaltyStatement::class, 'royalty_statement_id');
    }

    public function track()
    {
        return $this->belongsTo(Track::class);
    }

    public function allocations()
    {
        return $this->hasMany(RoyaltyAllocation::class, 'royalty_statement_line_id');
    }

    public static function allowedMatchStatuses(): array
    {
        return [
            self::MATCH_STATUS_MATCHED,
            self::MATCH_STATUS_UNMATCHED,
            self::MATCH_STATUS_AMBIGUOUS,
            self::MATCH_STATUS_DUPLICATE,
            self::MATCH_STATUS_REFERENCE_ONLY,
        ];
    }
}
