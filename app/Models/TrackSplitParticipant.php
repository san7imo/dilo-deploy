<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrackSplitParticipant extends Model
{
    use HasFactory, LogsAuditTrail, SoftDeletes;

    protected $fillable = [
        'track_split_agreement_id',
        'artist_id',
        'user_id',
        'payee_email',
        'name',
        'role',
        'percentage',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    public function agreement()
    {
        return $this->belongsTo(TrackSplitAgreement::class, 'track_split_agreement_id');
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function allocations()
    {
        return $this->hasMany(RoyaltyAllocation::class, 'track_split_participant_id');
    }
}
