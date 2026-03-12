<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExternalArtistInvitation extends Model
{
    use HasFactory, LogsAuditTrail, SoftDeletes;

    protected $fillable = [
        'email',
        'token_hash',
        'track_id',
        'track_split_participant_id',
        'invited_by',
        'accepted_user_id',
        'invitee_name',
        'expires_at',
        'accepted_at',
        'revoked_at',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function track()
    {
        return $this->belongsTo(Track::class);
    }

    public function participant()
    {
        return $this->belongsTo(TrackSplitParticipant::class, 'track_split_participant_id');
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function acceptedUser()
    {
        return $this->belongsTo(User::class, 'accepted_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isPending(): bool
    {
        return !$this->isAccepted() && !$this->isRevoked() && !$this->isExpired();
    }
}
