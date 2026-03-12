<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompositionRoyaltyStatement extends Model
{
    use HasFactory, LogsAuditTrail, SoftDeletes;

    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'provider',
        'source_name',
        'reporting_period',
        'reporting_month_date',
        'currency',
        'original_filename',
        'stored_path',
        'file_hash',
        'statement_key',
        'version',
        'is_current',
        'status',
        'total_lines',
        'total_units',
        'total_amount_usd',
        'error_message',
        'processed_at',
        'created_by',
    ];

    protected $casts = [
        'reporting_month_date' => 'date',
        'processed_at' => 'datetime',
        'is_current' => 'boolean',
        'total_amount_usd' => 'decimal:6',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(CompositionRoyaltyLine::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CompositionAllocation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

