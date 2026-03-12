<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoyaltyPayoutPayment extends Model
{
    use HasFactory, LogsAuditTrail;

    protected $fillable = [
        'royalty_payout_request_id',
        'amount_usd',
        'currency',
        'payment_method',
        'payment_reference',
        'paid_at',
        'description',
        'created_by',
    ];

    protected $casts = [
        'amount_usd' => 'decimal:6',
        'paid_at' => 'date',
    ];

    public function payoutRequest()
    {
        return $this->belongsTo(RoyaltyPayoutRequest::class, 'royalty_payout_request_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(RoyaltyPayoutItem::class, 'royalty_payout_payment_id');
    }
}
