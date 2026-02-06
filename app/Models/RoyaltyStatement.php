<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class RoyaltyStatement extends Model
{
    use HasFactory;

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
        'status',
        'total_units',
        'total_net_usd',
        'created_by',
    ];

    protected $casts = [
        'reporting_month_date' => 'date',
        'is_current' => 'boolean',
        'total_units' => 'integer',
        'total_net_usd' => 'decimal:6',
    ];

    public function lines()
    {
        return $this->hasMany(RoyaltyStatementLine::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
