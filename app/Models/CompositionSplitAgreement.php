<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompositionSplitAgreement extends Model
{
    use HasFactory, LogsAuditTrail, SoftDeletes;

    protected $fillable = [
        'composition_id',
        'status',
        'effective_from',
        'effective_to',
        'contract_path',
        'contract_original_filename',
        'contract_hash',
        'created_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function composition(): BelongsTo
    {
        return $this->belongsTo(Composition::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(CompositionSplitParticipant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
