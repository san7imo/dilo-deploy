<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompositionSplitParticipant extends Model
{
    use HasFactory, LogsAuditTrail, SoftDeletes;

    protected $fillable = [
        'composition_split_set_id',
        'composition_split_agreement_id',
        'artist_id',
        'user_id',
        'payee_email',
        'name',
        'role',
        'share_pool',
        'percentage',
    ];

    protected $casts = [
        'percentage' => 'decimal:4',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(CompositionSplitAgreement::class, 'composition_split_agreement_id');
    }

    public function splitSet(): BelongsTo
    {
        return $this->belongsTo(CompositionSplitSet::class, 'composition_split_set_id');
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allocations()
    {
        return $this->hasMany(RoyaltyAllocation::class, 'composition_split_participant_id');
    }

    public function compositionAllocations()
    {
        return $this->hasMany(CompositionAllocation::class, 'composition_split_participant_id');
    }
}
