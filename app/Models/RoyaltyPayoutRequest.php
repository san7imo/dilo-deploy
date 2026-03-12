<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoyaltyPayoutRequest extends Model
{
    use HasFactory, LogsAuditTrail;

    protected $fillable = [
        'requester_user_id',
        'requester_artist_id',
        'requester_name',
        'requester_email',
        'requested_amount_usd',
        'minimum_threshold_usd',
        'currency',
        'status',
        'requested_at',
        'processed_at',
        'processed_by',
        'admin_notes',
        'metadata',
    ];

    protected $casts = [
        'requested_amount_usd' => 'decimal:6',
        'minimum_threshold_usd' => 'decimal:6',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function requesterUser()
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function requesterArtist()
    {
        return $this->belongsTo(Artist::class, 'requester_artist_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function payments()
    {
        return $this->hasMany(RoyaltyPayoutPayment::class, 'royalty_payout_request_id');
    }
}
