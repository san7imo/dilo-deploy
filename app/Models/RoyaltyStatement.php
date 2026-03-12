<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class RoyaltyStatement extends Model
{
    use HasFactory, LogsAuditTrail, SoftDeletes;

    protected $fillable = [
        'provider',
        'label',
        'reporting_period',
        'reporting_month_date',
        'currency',
        'original_filename',
        'stored_path',
        'file_hash',
        'statement_key',
        'version',
        'is_current',
        'is_reference_only',
        'duplicate_of_statement_id',
        'status',
        'total_units',
        'total_net_usd',
        'created_by',
    ];

    protected $casts = [
        'reporting_month_date' => 'date',
        'is_current' => 'boolean',
        'is_reference_only' => 'boolean',
        'total_units' => 'integer',
        'total_net_usd' => 'decimal:6',
    ];

    public function lines()
    {
        return $this->hasMany(RoyaltyStatementLine::class);
    }

    public function allocations()
    {
        return $this->hasMany(RoyaltyAllocation::class);
    }

    public function duplicateOf()
    {
        return $this->belongsTo(self::class, 'duplicate_of_statement_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
